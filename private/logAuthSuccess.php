<?php
//
// Description
// -----------
// This function will log all success authentications via the API.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_users_logAuthSuccess($ciniki) {

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');

	$ip_address = 'unknown';
	if( isset($_SERVER['REMOTE_ADDR']) ) {
		$ip_address = $_SERVER['REMOTE_ADDR'];
	} else {
		$ip_address = 'localhost';
	}
	if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '' ) {
		$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}

	$strsql = "INSERT INTO user_auth_log (user_id, api_key, ip_address, log_date "
		. ") VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['api_key']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ip_address) . "', "
		. "UTC_TIMESTAMP() )";
	return ciniki_core_dbInsert($ciniki, $strsql, 'users');	
}
?>
