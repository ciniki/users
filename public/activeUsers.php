<?php
//
// Description
// -----------
// This method will return the list of active users, sorted by most recent.
//
// Arguments
// ---------
// api_key:
// auth_token:
// limit:				(optional) The maximum number of logs to return, default is 25.
//
// Returns
// -------
//
function ciniki_users_activeUsers($ciniki) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'),
		));
	$args = $rc['args'];

	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.activeUsers', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$date_format = ciniki_users_datetimeFormat($ciniki);

	// Sort the list ASC by date, so the oldest is at the bottom, and therefore will get 
	// insert at the top of the list in ciniki-manage
	$strsql = "SELECT ciniki_users.id, "
		. "DATE_FORMAT(MAX(a1.log_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS log_date "
		. ", CONCAT_WS(' ', ciniki_users.firstname, ciniki_users.lastname) AS name "
		. ", ciniki_users.display_name "
//		. ", api_key, ip_address, session_key "
		. "FROM ciniki_users "
		. "LEFT JOIN ciniki_user_auth_log AS a1 ON (ciniki_users.id = a1.user_id ) "
//		. "LEFT JOIN ciniki_user_auth_log AS a2 ON (ciniki_users.id = a2.user_id "
//			. "AND a1.log_date = a2.log_date) "
		. "GROUP BY ciniki_users.id "
		. "ORDER BY MAX(a1.log_date) DESC ";
	
	if( isset($args['limit']) && $args['limit'] != '' && is_numeric($args['limit'])) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} else {
		$strsql .= "LIMIT 25 ";
	}

	$rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
	return $rsp;
}
?>
