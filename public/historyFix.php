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
//
function ciniki_users_historyFix($ciniki) {
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.historyFix', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');

	//
	// Check for items that are missing a add value in history
	//
	$fields = array('uuid', 'username', 'email', 'firstname', 'lastname', 'display_name');
	foreach($fields as $field) {
		//
		// Get the list of users which don't have a history for the field
		//
		$strsql = "SELECT ciniki_users.id, ciniki_users.$field AS field_value, "
			. "UNIX_TIMESTAMP(ciniki_users.date_added) AS date_added, "
			. "UNIX_TIMESTAMP(ciniki_users.last_updated) AS last_updated "
			. "FROM ciniki_users "
			. "LEFT JOIN ciniki_user_history ON (ciniki_users.id = ciniki_user_history.table_key "
				. "AND ciniki_user_history.business_id = 0 "
				. "AND ciniki_user_history.table_name = 'ciniki_users' "
				. "AND (ciniki_user_history.action = 1 OR ciniki_user_history.action = 2) "
				. "AND ciniki_user_history.table_field = '" . ciniki_core_dbQuote($ciniki, $field) . "' "
				. ") "
			. "WHERE ciniki_users.$field <> '' "
			. "AND ciniki_users.$field <> '0000-00-00' "
			. "AND ciniki_users.$field <> '0000-00-00 00:00:00' "
			. "AND ciniki_user_history.uuid IS NULL "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'history');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	
		$elements = $rc['rows'];
		foreach($elements AS $rid => $row) {
			$strsql = "INSERT INTO ciniki_user_history (uuid, business_id, user_id, session, action, "
				. "table_name, table_key, table_field, new_value, log_date) VALUES ("
				. "UUID(), 0, "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
				. "'1', 'ciniki_users', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['field_value']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $row['date_added']) . "') "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Check for items missing a UUID
	//
	$strsql = "UPDATE ciniki_user_history SET uuid = UUID() WHERE uuid = ''";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
