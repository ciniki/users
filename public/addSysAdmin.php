<?php
//
// Description
// -----------
// This method adds the sysadmin priviledge to an existing user.  This 
// method is only accessible to sysadmins.
//
// Info
// ----
// publish: 		no
// 
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:				The user to add the sysadmin privledge to.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_users_addSysAdmin($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.addSysAdmin', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the user to set the sysadmin flag
	//
	$strsql = "UPDATE ciniki_users SET perms = perms | 0x01 "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	return ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
}
?>
