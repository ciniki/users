<?php
//
// Description
// -----------
// This function will change the users password, providing their own one is correct.
//
// Info
// ----
// status:			beta
// 
// Arguments
// ---------
// api_key:
// auth_token:
//
// oldpassword:	 	The old password for the user.  Done as a security measure so,
//					if somebody hijacks the session, they can't change the password.
//
// newpassword: 	The new password for the user.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_users_changePassword($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'oldpassword'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No old password specified'), 
		'newpassword'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No new password specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	if( strlen($args['newpassword']) < 8 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'121', 'msg'=>'New password must be longer than 8 characters.'));
	}

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.changePassword', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check old password
	//
	$strsql = "SELECT id, email FROM users "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['oldpassword']) . "') ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery(&$ciniki, $strsql, 'users', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Perform an extra check to make sure only 1 row was found, other return error
	//
	if( $rc['num_rows'] != 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'122', 'msg'=>'Invalid old password'));
	}

	//
	// Turn off autocommit
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the password, but only if the old one matches
	//
	$strsql = "UPDATE users SET password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['newpassword']) . "'), "
		. "last_updated = UTC_TIMESTAMP(), "
		. "last_pwd_change = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['oldpassword']) . "') ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbUpdate(&$ciniki, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'335', 'msg'=>'Unable to update password.'));
	}

	if( $rc['num_affected_rows'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'123', 'msg'=>'Unable to change password.'));
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'users');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'336', 'msg'=>'Unable to update password.'));
	}

	return array('stat'=>'ok');
}
?>
