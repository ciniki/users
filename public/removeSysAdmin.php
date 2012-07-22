<?php
//
// Description
// -----------
// This function will remove the sysadmin flag from a user.
//
// Info
// ----
// publish:			no
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:				The user to remove the sysadmin privledge from.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_users_removeSysAdmin($ciniki) {
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
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.removeSysAdmin', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check and verify there are other sys admins.  Don't want to delete the last
	// sys admin account, otherwise nobody can login
	//
	$strsql = "SELECT 'admins', COUNT(id) FROM ciniki_users "
		. "WHERE (perms & 0x01) = 0x01 "
		. "AND id != '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbCount.php');
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'users', 'count');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['count']['admins']) || $rc['count']['admins'] < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'202', 'msg'=>'Unable to remove last Sys Admin'));
	}

	//
	// Update the user information to remove the sysadmin flag
	//
	$strsql = "UPDATE ciniki_users SET perms = perms ^ 0x01 "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Also remove any active sessions for user to ensure no more damage
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/sessionEndUser.php');
	$rc = ciniki_core_sessionEndUser($ciniki, $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return $rc;
}
?>
