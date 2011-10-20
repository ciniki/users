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
// Status:			beta
// Publish:			yes
// 
// Arguments
// ---------
// api_key:
// auth_token:
// email:			The email address to be authenticated.  The email
//					address or username must be sent.
//
// username:	 	The username to be authenticated.  The username can be
//					an email address or username, they are both unique
//					in the database.
//
// password: 		The password to be checked with the username.
//
// Example Return
// --------------
// <auth token="0123456789abcdef0123456789abcdef" />
//
function ciniki_users_auth($ciniki) {
	if( (!isset($ciniki['request']['args']['username'])
		|| !isset($ciniki['request']['args']['email']))
		&& !isset($ciniki['request']['args']['password']) ) {
		//
		// This return message should be cryptic so people
		// can't use the error code to determine what went wrong
		//
		return array('stat'=>'fail', 'err'=>array('code'=>'8', 'msg'=>'Invalid password'));
	}

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.auth', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/sessionStart.php');
	return ciniki_core_sessionStart($ciniki, $ciniki['request']['args']['username'], $ciniki['request']['args']['password']);
}
?>
