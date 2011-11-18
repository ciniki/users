<?php
//
// Description
// -----------
// This function will return the list of users based on a list of IDs.  The returned
// list will be sorted by ID for each lookup.
//
// Arguments
// ---------
// ciniki:
// container_name:		The name of the container to store the user list in.
// ids:					The user IDs to be looked up in the database.
//
// Returns
// -------
// <users>
//		<1 id='1' email='' firstname='' lastname='' display_name='' perms=''/>
// </users>
//
function ciniki_users_userListByID($ciniki, $container_name, $ids, $fields) {

	if( !is_array($ids) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'250', 'msg'=>'Invalid list of users'));
	}

	if( count($ids) < 1 ) {
		return array('stat'=>'ok', 'users'=>array());
	}

	//
	// Query for the business users
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuoteIDs.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	if( $fields == 'all' ) {
		$strsql = "SELECT id, email, firstname, lastname, display_name, perms FROM ciniki_users ";
	} elseif( $fields == 'display_name' ) {
		$strsql = "SELECT id, display_name FROM ciniki_users ";
	}
	$strsql .= "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, array_unique($ids)) . ") "
		. "ORDER BY id ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery.php');
	return ciniki_core_dbHashIDQuery($ciniki, $strsql, 'users', $container_name, 'id');
}
?>
