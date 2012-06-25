<?php
//
// Description
// -----------
// This function will unlock a user account, resetting the login_attempts to 0, and remote the lock flag.
//
// Info
// ----
// status:			beta
// 
// Arguments
// ---------
// api_key:
// auth_token:
// user_id: 			The ID of the user to unlock the account for.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_users_unlock($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.unlock', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuoteRequestArg.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	$strsql = "UPDATE ciniki_users SET status = 1, login_attempts = 0 "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_dbAddModuleHistory($ciniki, 'users', 'ciniki_user_history', 0, 
		2, 'ciniki_users', $args['user_id'], 'status', '1');

	return $rc;
}
?>
