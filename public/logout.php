<?php
//
// Description
// -----------
// This function will check a email or username and password, 
// and create auth_token to return.  And email address or
// username may be sent.  
//
// Info
// ----
// status:			beta
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
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.logout', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['root_dir'] . '/ciniki-api/core/private/sessionEnd.php');

	return ciniki_core_sessionEnd($ciniki);
}
?>
