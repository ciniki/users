<?php
//
// Description
// -----------
// This method will set a users password.  Currently only accessible 
// to sysadmins.
//
// Info
// ----
// publish:			no
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:			The user to have the password set.
// password:		The new password to set for the user.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_users_setPassword($ciniki) {
	
	// FIXME: Require sysadmin password to verify user before allowing a set.

	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
		'password'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Password'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.setPassword', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
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
	// Update the password, but only if the old one matches
	//
	$strsql = "UPDATE ciniki_users SET password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['password']) . "') "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'432', 'msg'=>'Unable to set password.'));
	}

	if( $rc['num_affected_rows'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'433', 'msg'=>'Unable to set password.'));
	}

	//
	// FIXME: Add log entry to track password changes
	//

	//
	// Commit the changes and return
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'434', 'msg'=>'Unable to set password.'));
	}

	return array('stat'=>'ok');
}
?>
