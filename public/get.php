<?php
//
// Description
// -----------
// This function will get detail values for a user.  These values
// are used many places in the API and Manage.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:				The ID of the user to get the details for.
// keys:				The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//		<user firstname='' lastname='' display_name=''/>
//  	<settings date_format='' />
// </details>
//
function ciniki_users_get($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access, should only be accessible by sysadmin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.get', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQuery.php');

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$date_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get all the information form ciniki_users table
	//
	$strsql = "SELECT id, avatar_id, email, username, perms, status, timeout, "
		. "firstname, lastname, display_name, login_attempts, "
		. "DATE_FORMAT(date_added, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_added, "
		. "DATE_FORMAT(last_updated, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS last_updated, "
		. "DATE_FORMAT(last_login, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS last_login, "
		. "DATE_FORMAT(last_pwd_change, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS last_pwd_change "
		. "FROM ciniki_users "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'511', 'msg'=>'Unable to find user'));
	}
	$user = $rc['user'];

	//
	// Get all the businesses the user is a part of
	//
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.name "
		. "FROM ciniki_business_users, ciniki_businesses "
		. "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
		. "AND ciniki_business_users.business_id = ciniki_businesses.id "
		. "AND ciniki_business_users.status = 1 "
		. "";
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'businesses', 'business', array('stat'=>'ok', 'businesses'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$user['businesses'] = $rc['businesses'];

	return array('stat'=>'ok', 'user'=>$user);
}
?>
