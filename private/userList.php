<?php
//
// Description
// -----------
// This method will merge in the field user_display_name when a record contains the variable user_id.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
function ciniki_users_mergeDisplayNames($ciniki, $hash) {

	if( !is_array($ids) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'249', 'msg'=>'Invalid list of users'));
	}

	//
	// Query for the business users
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuoteIDs.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	$strsql = "SELECT id, email, firstname, lastname, display_name, perms FROM ciniki_users "
		. "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
		. "ORDER BY lastname, firstname ";
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'users', $container_name, 'user', array('stat'=>'ok', $container_name=>array()));
}
?>
