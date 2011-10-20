<?php
//
// Description
// -----------
// This function will check if the user has access to a specified module and function.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// ciniki:
// business_id:			The business ID to check the session user against.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_users_checkAccess($ciniki, $business_id, $method, $user_id) {
	//
	// Methods which don't require authentication
	//
	$noauth_methods = array(
		'ciniki.users.auth',
		);
	if( in_array($method, $noauth_methods) ) {
		return array('stat'=>'ok');
	}


	//
	// Check the user is authenticated
	//
	if( !isset($ciniki['session'])
		|| !isset($ciniki['session']['user'])
		|| !isset($ciniki['session']['user']['id'])
		|| $ciniki['session']['user']['id'] < 1 ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'103', 'msg'=>'User not authenticated'));
	}

	//
	// Check if the requested method is a public method
	//
	$public_methods = array(
		'ciniki.users.changePassword',
		'ciniki.users.logout',
		'ciniki.users.formatDate',
		);
	if( in_array($method, $public_methods) ) {
		return array('stat'=>'ok');
	}

	//
	// If the user is a sysadmin, they have access to all functions
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok');
	}

	//
	// Some methods should be available to both sys admins, and the user.  Verify
	// the user requesting the change is the same as the user to be changed.
	//
	$user_methods = array(
		'ciniki.users.getDetailHistory',
		'ciniki.users.getDetails',
		'ciniki.users.getAvatar',
		'ciniki.users.uploadAvatar',
		'ciniki.users.updateDetails',
		);
	if( in_array($method, $user_methods) && $user_id > 0 && $ciniki['session']['user']['id'] == $user_id ) {
		return array('stat'=>'ok');
	}

	//
	// By default fail
	//
	return array('stat'=>'fail', 'err'=>array('code'=>'69', 'msg'=>'Access denied'));
}
?>
