<?php
//
// Description
// -----------
// This method will search the existing users for a username.
//
// Info
// ----
// publish:			no
//
// Arguments
// ---------
// api_key:
// auth_token:
// start_needle:	The string to search for a matching username.
// search_limit:	(optional) The maximum number of entries to return. 
//					If not specified, the default limit is 11.
// 
// Returns
// -------
// <rsp stat="ok">
//		<users>
//			<user id="3421" firstname="Andrew" lastname="Rivett" display_name="Andrew R" email="andrew@ciniki.ca" />
//			...
//		</users>
// </rsp>
//
function ciniki_users_searchUsername($ciniki) {
	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.searchUsername', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No start_needle specified'), 
		'search_limit'=>array('required'=>'no', 'default'=>'11', 'blank'=>'yes', 'errmsg'=>'No search limit specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Query for the users with a username that starts with the 'start_needle' passed from through the API
	//
	$strsql = "SELECT id, firstname, lastname, display_name, username FROM ciniki_users "
		. "WHERE username LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
		. "LIMIT " . ciniki_core_dbQuote($ciniki, $args['search_limit']) . " ";

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
}
?>
