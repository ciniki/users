<?php
//
// Description
// -----------
// This function will remove the sysadmin flag from a user.
//
// Info
// ----
// Status: beta
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
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.removeSysAdmin', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check if the user_id was specified
	//
	if( !isset($ciniki['request']['args']['user_id']) || $ciniki['request']['args']['user_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'179', 'msg'=>'No user selected.'));
	}
	$user_id = $ciniki['request']['args']['user_id'];

	//
	// Check and verify there are other sys admins.  Don't want to delete the last
	// sys admin account, otherwise nobody can login
	//
	$strsql = "SELECT 'admins', COUNT(id) FROM users "
		. "WHERE (perms & 0x01) = 0x01 "
		. "AND id != '" . ciniki_core_dbQuote($ciniki, $user_id) . "'";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbCount.php');
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'users', 'count');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['count']['admins']) || $rc['count']['admins'] < 1 ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'202', 'msg'=>'Unable to remove last Sys Admin'));
	}

	//
	// Update the user information to remove the sysadmin flag
	//
	$strsql = "UPDATE users SET perms = perms ^ 0x01 "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $user_id) . "'";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Also remove any active sessions for user to ensure no more damage
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/sessionEndUser.php');
	$rc = ciniki_core_sessionEndUser($ciniki, $user_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return $rc;
}
?>
