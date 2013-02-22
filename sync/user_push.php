<?php
//
// Description
// -----------
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_users_user_push(&$ciniki, &$sync, $business_id, $args) {
	if( !isset($args['id']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'80', 'msg'=>'Missing ID argument'));
	}

	//
	// Get the local user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'sync', 'user_get');
	$rc = ciniki_users_user_get($ciniki, $sync, $business_id, array('id'=>$args['id']));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'79', 'msg'=>'Unable to get user'));
	}
	if( !isset($rc['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'78', 'msg'=>'User not found on remote server'));
	}
	$user = $rc['user'];

	//
	// Update the remote user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.users.user.update', 'user'=>$user));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'77', 'msg'=>'Unable to sync user'));
	}

	return array('stat'=>'ok');
}
?>
