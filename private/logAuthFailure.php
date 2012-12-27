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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'alertGenerate');

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
	$strsql = "INSERT INTO ciniki_user_auth_failures (username, api_key, ip_address, log_date, code"
		. ") VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $username) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['api_key']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ip_address) . "', "
		. "UTC_TIMESTAMP(), "
		. "'" . ciniki_core_dbQuote($ciniki, $err_code) . "')";
	
	ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');

	//
	// Update the login attempts if the username is valid.
	// 
	if( $username != '' ) {
		//
		// Check for that username and see if there is a match
		//
		if( preg_match('/\@/', $username) ) {
			$strsql = "SELECT id, email, username, firstname, lastname, status, login_attempts, date_added, last_login, last_pwd_change "
				. "FROM ciniki_users "
				. "WHERE email = '" . ciniki_core_dbQuote($ciniki, $username) . "' ";
		} else {
			$strsql = "SELECT id, email, username, firstname, lastname, status, login_attempts, date_added, last_login, last_pwd_change "
				. "FROM ciniki_users "
				. "WHERE username = '" . ciniki_core_dbQuote($ciniki, $username) . "' ";
		}

		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
		if( $rc['stat'] == 'ok' && isset($rc['user'])) {
			//
			// Check if account should be locked
			//
			if( $rc['user']['status'] < 10 && $rc['user']['login_attempts'] > 6 ) {
				$strsql = "UPDATE ciniki_users SET status = 10 "
					. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $rc['user']['id']) . "' "
					. "AND status < 10";
				ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
				print "ciniki_core_alertGenerate\n";
				ciniki_core_alertGenerate($ciniki, 
					array('alert'=>'2', 'msg'=>"Account '$username' locked"), null);
			}

			$strsql = "UPDATE ciniki_users SET login_attempts = login_attempts + 1 "
				. "WHERE username = '" . ciniki_core_dbQuote($ciniki, $username) . "' "
				. "AND login_attempts < 100 ";
			ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
		}
	}

	return array('stat'=>'ok');
}
?>
