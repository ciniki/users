<?php
//
// Description
// -----------
// This method will authenticate a user and return an auth_token
// to be used for future API calls.  Either a username or 
// email address can be used to authenticate.
//
// Info
// ----
// publish:			yes
//
// Arguments
// ---------
// api_key:
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
// <rsp stat="ok" business="3">
//		<auth token="0123456789abcdef0123456789abcdef" id="42" perms="1" avatar_id="192" />
// </rsp>
//
function ciniki_users_auth(&$ciniki) {
	if( (!isset($ciniki['request']['args']['username'])
		|| !isset($ciniki['request']['args']['email']))
		&& !isset($ciniki['request']['args']['password']) ) {
		//
		// This return message should be cryptic so people
		// can't use the error code to determine what went wrong
		//
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'8', 'msg'=>'Invalid password'));
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
	$rc = ciniki_core_sessionStart($ciniki, $ciniki['request']['args']['username'], $ciniki['request']['args']['password']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$auth = $rc['auth'];

	//
	// If the user is not a sysadmin, check if they only have access to one business
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0 ) {
		$strsql = "SELECT DISTINCT ciniki_businesses.id, name "
			. "FROM ciniki_business_users, ciniki_businesses "
			. "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND ciniki_business_users.status = 1 "
			. "AND ciniki_business_users.business_id = ciniki_businesses.id "
			. "AND ciniki_businesses.status < 60 "	// Allow suspended businesses to be listed, so user can login and update billing/unsuspend
			. "ORDER BY ciniki_businesses.name "
			. "LIMIT 2"
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['business']) ) {
			return array('stat'=>'ok', 'auth'=>$auth, 'business'=>$rc['business']['id']);
		}
	}	

	return array('stat'=>'ok', 'auth'=>$auth);
}
?>
