<?php
//
// Description
// -----------
// This method will get the user authentication failures.
//
// Info
// ----
// publish: 		no
//
// Arguments
// ---------
// api_key:
// auth_token:
// last_timestamp:  		The last timestamp from the previous request.
//
// Example Return
// --------------
// <rsp stat="ok">
// 		<logs timestamp=''>
//			<log id='4928' date="2011/02/03 00:03:00" value="Value field set to" user_id="1" display_name="" />
// 		</logs>
// </rsp>
//
function ciniki_users_monitorAuthFailures($ciniki) {
	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.monitorAuthFailures', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuoteRequestArg.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');

	$strsql = "SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) as cur";
	$ts = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'timestamp');
	if( $ts['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'244', 'msg'=>'No timestamp available'));
	}

    //
	// Verify the field was passed, and is valid.
	//
	$last_timestamp = $ts['timestamp']['cur'] - 43200;		// Get anything that is from the last 12 hours by default.
	$last_timestamp = $ts['timestamp']['cur'] - 604800;		// Get anything that is from the last 24 hours by default.
	$req_last_timestamp = $last_timestamp;
	if( isset($ciniki['request']['args']['last_timestamp']) && $ciniki['request']['args']['last_timestamp'] != '' ) {
		$req_last_timestamp = ciniki_core_dbQuoteRequestArg($ciniki, 'last_timestamp');
	}
	// Force last_timestamp to be no older than 1 week
	if( $req_last_timestamp < ($ts['timestamp']['cur'] - 604800) ) {
		$req_last_timestamp = $ts['timestamp']['cur'] - 604800;
	}

	$session = '';
	if( isset($ciniki['request']['args']['session']) ) {
		$session = ciniki_core_dbQuoteRequestArg($ciniki, 'session');
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$date_format = ciniki_users_datetimeFormat($ciniki);

	// Sort the list ASC by date, so the oldest is at the bottom, and therefore will get 
	// insert at the top of the list in ciniki-manage
	$strsql = "SELECT DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as log_date"
		. ", CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age "
		. ", UNIX_TIMESTAMP(log_date) as TS"
		. ", username, api_key, ip_address "
		. "FROM ciniki_user_auth_failures "
		. "WHERE UNIX_TIMESTAMP(ciniki_user_auth_failures.log_date) > '" . ciniki_core_dbQuote($ciniki, $req_last_timestamp) . "' "
		. "ORDER BY TS DESC ";
	$rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'logs', 'log', array('stat'=>'ok', 'logs'=>array()));
	if( $rsp['stat'] == 'ok' ) {
		$rsp['timestamp'] = $ts['timestamp']['cur'];
	}

	return $rsp;
}
?>
