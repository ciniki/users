<?php
//
// Description
// -----------
// This function will get the history of a field from the ciniki_core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Info
// ----
// publish:			yes
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:					The user ID to get the history detail for.
// field:					The detail key to get the history for.
//
// Returns
// -------
//	<history>
//		<action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//		...
//	</history>
//	<users>
//		<user id="1" name="users.display_name" />
//		...
//	</users>
//
function ciniki_users_getDetailHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No field specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.getDetailHistory', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}


	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	if( $args['field'] == 'user.firstname' ) {
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 'ciniki_users', $args['user_id'], 'firstname');
	} elseif( $args['field'] == 'user.lastname' ) {
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 'ciniki_users', $args['user_id'], 'lastname');
	} elseif( $args['field'] == 'user.display_name' ) {
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 'ciniki_users', $args['user_id'], 'display_name');
	}

	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 'ciniki_user_details', $args['user_id'], $args['field']);
}
?>
