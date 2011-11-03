<?php
//
// Description
// -----------
// This function is used by other methods internally to get a list of users
// and some information about them.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <users>
//		<user id='1' email='' firstname='' lastname='' display_name='' perms=''/>
// </users>
//
function ciniki_users_userList($ciniki, $container_name, $ids) {

	if( !is_array($ids) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'995', 'msg'=>'Invalid list of users'));
	}

	//
	// Query for the business users
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuoteIDs.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	$strsql = "SELECT id, email, firstname, lastname, display_name, perms FROM users "
		. "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") ";
		. "ORDER BY lastname, firstname ";
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'users', $container_name, 'user', array('stat'=>'ok', $container_name=>array()));
}
?>
