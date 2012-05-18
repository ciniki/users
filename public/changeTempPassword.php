<?php
//
// Description
// -----------
// This function will change the users password, providing their own one is correct.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// temppassword: 	The temporary password for the user.  
//
// newpassword: 	The new password for the user.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_users_changeTempPassword($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'email'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No email specified'), 
		'temppassword'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No temporary password specified'), 
		'newpassword'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No new password specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	if( strlen($args['newpassword']) < 8 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'461', 'msg'=>'New password must be longer than 8 characters.'));
	}

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.changeTempPassword', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check temp password
	// Must change password within 30 minutes (1800 seconds)
	$strsql = "SELECT id, email FROM ciniki_users "
		. "WHERE email = '" . ciniki_core_dbQuote($ciniki, $args['email']) . "' "
		. "AND temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['temppassword']) . "') "
		. "AND (UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(temp_password_date)) < 1800 "
		. "";
	error_log($strsql);
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'users', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['user']) || !is_array($rc['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'466', 'msg'=>'Unable to update password.'));
	}
	$user = $rc['user'];

	//
	// Perform an extra check to make sure only 1 row was found, other return error
	//
	if( $rc['num_rows'] != 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'462', 'msg'=>'Invalid temporary password'));
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
	// Update the password, but only if the temporary one matches
	//
	$strsql = "UPDATE ciniki_users SET password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['newpassword']) . "'), "
		. "temp_password = '', "
		. "last_updated = UTC_TIMESTAMP(), "
		. "last_pwd_change = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
		. "AND temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['temppassword']) . "') ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbUpdate(&$ciniki, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'463', 'msg'=>'Unable to update password.'));
	}

	if( $rc['num_affected_rows'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'464', 'msg'=>'Unable to change password.'));
	}

	//
	// Commit all the changes to the database
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'users');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'465', 'msg'=>'Unable to update password.'));
	}

	return array('stat'=>'ok');
}
?>
