<?php
//
// Description
// -----------
// This method will set a users password.  The temporary password provided by the 
// ciniki.users.passwordRequestReset must be provided to set a new password.  
//
// Info
// ----
// publish:			yes
//
// Arguments
// ---------
// api_key:
//
// email:			The email address of the user 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'email'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Email'), 
		'temppassword'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Temporary Password'), 
		'newpassword'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'New Password'), 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.changeTempPassword', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check temp password
	// Must change password within 30 minutes (1800 seconds)
    //
	$strsql = "SELECT id, email, temp_password, (UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(temp_password_date)) AS temp_password_age "
        . "FROM ciniki_users "
		. "WHERE email = '" . ciniki_core_dbQuote($ciniki, $args['email']) . "' "
//		. "AND temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['temppassword']) . "') "
//		. "AND (UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(temp_password_date)) < 1800 "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['user']) || !is_array($rc['user']) ) {
//		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'466', 'msg'=>'Time has expired, you must reset your password within 30 minutes of getting the email.'));
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'466', 'msg'=>'Could not find the email address.'));
	}
	$user = $rc['user'];
    
    if( $user['temp_password'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3334', 'msg'=>'Your password has already been reset. You can request a new activation code below.'));
    }

    if( $user['temp_password_age'] > 1800 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3335', 'msg'=>"It has been more than 30 minutes, you'll need to request a new activation code."));
    }

	//
	// Perform an extra check to make sure only 1 row was found, other return error
	//
	if( $rc['num_rows'] != 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'462', 'msg'=>'Invalid temporary password'));
	}

	//
	// Turn off autocommit
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.users');
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'463', 'msg'=>'Unable to update password.'));
	}

//  Don't worry if password was reset to same password, more errors confuse users
//	if( $rc['num_affected_rows'] < 1 ) {
//		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
//		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'464', 'msg'=>'Unable to change password.'));
//	}

	//
	// Commit all the changes to the database
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'465', 'msg'=>'Unable to update password.'));
	}

	return array('stat'=>'ok');
}
?>
