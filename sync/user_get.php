<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
// $user_uuid => Array(
//		[uuid] => d704ebb4-5554-11e2-9d3d-e17298a56f3f
//		[id] => 3
//		[email] => test@doun.org
//		[username] => test1
//		[firstname] => Test1
//		[lastname] => last
//		[status] => 1
//		[timeout] => 0
//		[display_name] => Test1
//		[date_added] => 1357201364
//		[last_updated] => 1357277499
//		[user_details] => Array(
//			[settings.datetime_format] => Array(
//				[detail_value] => %b %3, %Y %l:%i %p
//				[date_added] => 1231344322
//				[last_updated] => 1231344322
//				[history] => Array(
//					...
//				)
//			)
//			[settings.date_format] => Array(
//				[detail_value] => %b %3, %Y
//				[date_added] => 1231344322
//				[last_updated] => 1231344322
//				[history] => Array(
//				)
//			)
//		)
//		[history] => Array(
//					...
//		)
// )
//
function ciniki_users_user_get($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['id']) || $args['id'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'92', 'msg'=>'No user specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Get the user information
	//
	$strsql = "SELECT u1.uuid, "
		. "u1.id, u1.email, u1.username, u1.firstname, u1.lastname, "
		. "u1.timeout, u1.display_name, u1.status, "
		. "UNIX_TIMESTAMP(u1.date_added) AS date_added, "
		. "UNIX_TIMESTAMP(u1.last_updated) AS last_updated, "
		. "ciniki_user_history.id AS history_id, "
		. "ciniki_user_history.uuid AS history_uuid, "
		. "u2.uuid AS user_uuid, "
		. "ciniki_user_history.session, "
		. "ciniki_user_history.action, "
		. "ciniki_user_history.table_field, "
		. "ciniki_user_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_user_history.log_date) AS log_date "
		. "FROM ciniki_users AS u1 "
		. "LEFT JOIN ciniki_business_users ON (u1.id = ciniki_business_users.user_id "
			. "AND ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
		. "LEFT JOIN ciniki_user_history ON (u1.id = ciniki_user_history.table_key "
			. "AND ciniki_user_history.business_id = '0' "
			. "AND ciniki_user_history.table_name = 'ciniki_users' "
			. ") "
		. "LEFT JOIN ciniki_users AS u2 ON (ciniki_user_history.user_id = u2.id) "
		. "";
	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		$strsql .= "WHERE u1.uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' ";
	} elseif( isset($args['id']) && $args['id'] != '' ) {
		$strsql .= "WHERE u1.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'91', 'msg'=>'No user specified'));
	}
	$strsql .= "ORDER BY u1.uuid ";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'users', 'fname'=>'uuid', 
			'fields'=>array('uuid', 'id', 'email', 'username', 'firstname', 'lastname', 
				'timeout', 'display_name', 'status', 'date_added', 'last_updated')),
		array('container'=>'history', 'fname'=>'history_uuid',
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'85', 'msg'=>'Error retrieving the user information', 'err'=>$rc['err']));
	}

	//
	// Check that one and only one row was returned
	//
	if( !isset($rc['users']) || count($rc['users']) != 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'82', 'msg'=>'User does not exist'));
	}
	$user = array_pop($rc['users']);

	//
	// Get details for user
	//
	$strsql = "SELECT ciniki_user_details.detail_key, "
		. "ciniki_user_details.detail_value, "
		. "UNIX_TIMESTAMP(ciniki_user_details.date_added) AS detail_date_added, "
		. "UNIX_TIMESTAMP(ciniki_user_details.last_updated) AS detail_last_updated, "
		. "ciniki_user_history.id AS history_id, "
		. "ciniki_user_history.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "ciniki_user_history.session, "
		. "ciniki_user_history.action, "
		. "ciniki_user_history.table_field, "
		. "ciniki_user_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_user_history.log_date) AS log_date "
		. "FROM ciniki_user_details "
		. "LEFT JOIN ciniki_user_history ON (ciniki_user_details.detail_key = ciniki_user_history.table_key "
			. "AND ciniki_user_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_user_history.table_name = 'ciniki_user_details' "
			. ") "
		. "LEFT JOIN ciniki_users ON (ciniki_user_history.user_id = ciniki_users.id) "
		. "WHERE ciniki_user_details.user_id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.users', array(
		array('container'=>'details', 'fname'=>'detail_key', 
			'fields'=>array('detail_value', 'date_added'=>'detail_date_added', 'last_updated'=>'detail_last_updated')),
		array('container'=>'history', 'fname'=>'history_uuid',
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'81', 'msg'=>'Error retrieving the user information', 'err'=>$rc['err']));
	}
	if( !isset($rc['details']) ) {
		$user['user_details'] = array();
	} else {
		$user['user_details'] = $rc['details'];
	}

	return array('stat'=>'ok', 'object'=>$user);
}
?>
