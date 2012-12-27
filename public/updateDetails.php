<?php
//
// Description
// -----------
// This method will update details for a user.
//
// Info
// ----
// publish:			yes
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:					The ID of the user to update the details for.
// user.firstname:			(optional) The new firstname for the user.
// user.lastname:			(optional) The new lastname for the user.
// user.display_name:		(optional) The new display_name for the user.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_users_updateDetails(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		'user.firstname'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No first name specified'), 
		'user.lastname'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No last name specified'), 
		'user.display_name'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No display name specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.updateDetails', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

	//
	// Turn off autocommit
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check if name or tagline was specified
	//
	$strsql = "";
	if( isset($ciniki['request']['args']['user.firstname']) && $ciniki['request']['args']['user.firstname'] != '' ) {
		$strsql .= ", firstname = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['user.firstname']) . "'";
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 2, 'ciniki_users', $args['user_id'], 'firstname', $args['user.firstname']);
	}
	if( isset($ciniki['request']['args']['user.lastname']) && $ciniki['request']['args']['user.lastname'] != '' ) {
		$strsql .= ", lastname = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['user.lastname']) . "'";
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 2, 'ciniki_users', $args['user_id'], 'lastname', $args['user.lastname']);
	}
	if( isset($ciniki['request']['args']['user.display_name']) && $ciniki['request']['args']['user.display_name'] != '' ) {
		$strsql .= ", display_name = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['user.display_name']) . "'";
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 2, 'ciniki_users', $args['user_id'], 'display_name', $args['user.display_name']);
	}
	//
	// Always update at least the last_updated field so it will be transfered with sync
	//
	$strsql = "UPDATE ciniki_users SET last_updated = UTC_TIMESTAMP()" . $strsql 
		. " WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'333', 'msg'=>'Unable to add user', 'err'=>$rc['err']));
	}

	//
	// Allowed user detail keys 
	//
	$allowed_keys = array(
		'ui.history.date.display',
		'settings.date_format',
		'settings.datetime_format',
		);
	foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
		if( in_array($arg_name, $allowed_keys) ) {
			$strsql = "INSERT INTO ciniki_user_details (user_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $arg_name) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $arg_value) . "', "
				. "UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $arg_value) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
				return $rc;
			}
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 2, 'ciniki_user_details', $args['user_id'], $arg_name, $arg_value);
		}
	}

	//
	// Update session values
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryHash');
	$rc = ciniki_core_dbDetailsQueryHash($ciniki, 'ciniki_user_details', 'user_id', $ciniki['session']['user']['id'], 'settings', 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
		return $rc;
	}
	if( isset($rc['details']['settings']) && $rc['details']['settings'] != null ) {
		$ciniki['session']['user']['settings'] = $rc['details']['settings'];
	}

	//
	// Commit the database changes
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'334', 'msg'=>'Unable to update user detail', 'err'=>$rc['err']));
	}

	return array('stat'=>'ok');
}
?>
