<?php
//
// Description
// -----------
// This method will clear all session data for a user, logging them out of the system.
//
// Info
// ----
// publish:			yes
// 
// Arguments
// ---------
// api_key:
// auth_token: 
//
// Example Return
// --------------
// <rsp stat="ok" />
//
function ciniki_users_logout($ciniki) {
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.logout', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sessionEnd');

	return ciniki_core_sessionEnd($ciniki);
}
?>
