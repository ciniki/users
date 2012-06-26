<?php
//
// Description
// -----------
// Upload a new image to be the users avatar
//
// Info
// ----
// Status: defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Example Return
// --------------
// <rsp stat="ok" id="4" />
//
function ciniki_users_deleteAvatar(&$ciniki) {
	//
	// Check args
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.uploadAvatar', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Start transaction
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'users');
	if( $rc['stat'] != 'ok' ) { 
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'453', 'msg'=>'Internal Error', 'err'=>$rc['err']));
	}   

	//
	// The image type will be checked in the insertFromUpload method to ensure it is an image
	// in a format we accept
	//

	//
	// Remove existing avatar
	//
	$strsql = "SELECT avatar_id FROM ciniki_users WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'users', 'user');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return $rc;
	}
	$avatar_id = $rc['user']['avatar_id'];

	if( $avatar_id > 0 ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/images/private/removeImage.php');
		$rc = ciniki_images_removeImage($ciniki, 0, $args['user_id'], $avatar_id);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'users');
			return $rc;
		}
	}

	//
	// Update user with new image id
	//
	$strsql = "UPDATE ciniki_users SET avatar_id = 0 "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Update the session variable, if same user who's logged in
	//
	if( $ciniki['session']['user']['id'] == $args['user_id'] ) {
		$ciniki['session']['user']['avatar_id'] = 0;
	}

	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'users');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'439', 'msg'=>'Unable to upload avatar', 'err'=>$rc['err']));
	}

	return array('stat'=>'ok');
}
?>
