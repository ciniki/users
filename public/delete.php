<?php
//
// Description
// -----------
// This method will mark the user as deleted.
//
// Info
// ----
// publish: 			no
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:				The user to remove the sysadmin privledge from.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_users_delete($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.delete', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the user information to remove the sysadmin flag
	//
	$strsql = "UPDATE ciniki_users SET status = 11 "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	// Log delete
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 2, 'ciniki_users', $args['user_id'], 'status', '11');

	return $rc;
}
?>
