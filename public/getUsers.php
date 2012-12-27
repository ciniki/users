<?php
//
// Description
// -----------
// This function will get all users who have elevated priviledges.  They
// can be sysadmins, business owners
//
// Info
// ----
// publish:			no
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <users>
//		<user id='1' email='' firstname='' lastname='' display_name='' perms=''/>
// </users>
//
function ciniki_users_getUsers($ciniki) {
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.getUsers', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Query for the business users
	//
	$strsql = "SELECT id, email, firstname, lastname, display_name, status, perms FROM ciniki_users "
		. "ORDER BY lastname, firstname ";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
}
?>
