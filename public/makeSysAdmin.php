<?php
//
// Description
// -----------
// This method will set the sysadmin flag for a user.
//
// Info
// ----
// publish:			no
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:				The ID of the user to remove the sysadmin privledge from.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_users_makeSysAdmin($ciniki) {
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
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.makeSysAdmin', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the user information to remove the sysadmin flag
	//
	$strsql = "UPDATE ciniki_users SET perms = perms | 0x01 "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return $rc;
}
?>
