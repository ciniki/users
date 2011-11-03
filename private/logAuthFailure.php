<?php
//
// Description
// -----------
// This function will log any failed attempts to authenticated through the API.
//
// Arguments
// ---------
// ciniki:			
// username:			The username to log the failure against.
// err_code:			The authentication failure error code.
//
// Returns
// -------
//
function ciniki_users_logAuthFailure($ciniki, $username, $err_code) {

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/alertGenerate.php');

	//
	// Add the login attempt to the log table
	//
	$ip_address = 'unknown';
	if( isset($_SERVER['REMOTE_ADDR']) ) {
		$ip_address = $_SERVER['REMOTE_ADDR'];
	}
	if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '' ) {
		$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	$strsql = "INSERT INTO user_auth_failures (username, api_key, ip_address, log_date, code"
		. ") VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $username) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['api_key']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ip_address) . "', "
		. "UTC_TIMESTAMP(), "
		. "'" . ciniki_core_dbQuote($ciniki, $err_code) . "')";
	
	ciniki_core_dbInsert($ciniki, $strsql, 'users');

	//
	// Update the login attempts if the username is valid.
	// 
	if( $username != '' ) {
		//
		// Check for that username and see if there is a match
		//
		if( preg_match('/\@/', $username) ) {
			$strsql = "SELECT id, email, username, firstname, lastname, status, login_attempts, date_added, last_login, last_pwd_change "
				. "FROM users "
				. "WHERE email = '" . ciniki_core_dbQuote($ciniki, $username) . "' ";
		} else {
			$strsql = "SELECT id, email, username, firstname, lastname, status, login_attempts, date_added, last_login, last_pwd_change "
				. "FROM users "
				. "WHERE username = '" . ciniki_core_dbQuote($ciniki, $username) . "' ";
		}

		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'users', 'user');
		if( $rc['stat'] == 'ok' && isset($rc['user'])) {
			//
			// Check if account should be locked
			//
			if( $rc['user']['status'] < 10 && $rc['user']['login_attempts'] > 6 ) {
				$strsql = "UPDATE users SET status = 10 "
					. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $rc['user']['id']) . "' "
					. "AND status < 10";
				ciniki_core_dbUpdate($ciniki, $strsql, 'users');
				print "ciniki_core_alertGenerate\n";
				ciniki_core_alertGenerate($ciniki, 
					array('alert'=>'2', 'msg'=>"Account '$username' locked"), null);
			}

			$strsql = "UPDATE users SET login_attempts = login_attempts + 1 "
				. "WHERE username = '" . ciniki_core_dbQuote($ciniki, $username) . "' "
				. "AND login_attempts < 100 ";
			ciniki_core_dbUpdate($ciniki, $strsql, 'users');
		}
	}

	return array('stat'=>'ok');
}
?>
