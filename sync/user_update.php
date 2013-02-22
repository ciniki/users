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
function ciniki_users_user_update(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['object']) || $args['object'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'890', 'msg'=>'No user specified'));
	}

	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		//
		// Get the remote user to update
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"ciniki.users.user.get", 'uuid'=>$args['uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'985', 'msg'=>"Unable to get the remote user", 'err'=>$rc['err']));
		}
		if( !isset($rc['object']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'984', 'msg'=>"user not found on remote server"));
		}
		$remote_user = $rc['object'];
	} else {
		$remote_user = $args['object'];
	}

	//
	// Check if user already exists in local server, and if not run the add script
	//
	$strsql = "SELECT id FROM ciniki_users "
		. "WHERE ciniki_users.uuid = '" . ciniki_core_dbQuote($ciniki, $remote_user['uuid']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1000', 'msg'=>'Unable to get user id', 'err'=>$rc['err']));
	}
	if( !isset($rc['user']) ) {
		//
		// Check to see if the email address is under another uuid
		//
		$strsql = "SELECT id, uuid FROM ciniki_users "
			. "WHERE ciniki_users.email = '" . ciniki_core_dbQuote($ciniki, $remote_user['email']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1001', 'msg'=>'Unable to get user id', 'err'=>$rc['err']));
		}
		if( !isset($rc['user']) ) {
			//
			// User does not exist at all
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'sync', 'user_add');
			$rc = ciniki_users_user_add($ciniki, $sync, 0, $args);
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1002', 'msg'=>'Unable to add user: ' . $remote_user['uuid'], 'err'=>$rc['err']));
		} else {
			//
			// Make sure the uuid map is in added
			//
			$user_id = $rc['user']['id'];
			$user_uuid = $rc['user']['uuid'];
			if( !isset($sync['uuidmaps']['ciniki_users'][$remote_user['uuid']]) ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateUUIDMap');
				$rc = ciniki_core_syncUpdateUUIDMap($ciniki, $sync, $business_id, 'ciniki_users', $remote_user['uuid'], $user_id);
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1003', 'msg'=>'Unable to update user uuidmaps: ' . $remote_user['uuid'], 'err'=>$rc['err']));
				}
			}
		}
	} else {
		$user_id = $rc['user']['id'];
	}
	$db_updated = 0;

	//
	// Get the local user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'sync', 'user_get');
	$rc = ciniki_users_user_get($ciniki, $sync, $business_id, array('id'=>$user_id));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1004', 'msg'=>'Unable to get local user: ' . $remote_user['uuid'], 'err'=>$rc['err']));
	}
	if( !isset($rc['object']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'58', 'msg'=>'User not found on local server'));
	}
	$local_user = $rc['object'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectDetailSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Compare basic elements of user
	//
	$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, 0, $remote_user, $local_user, array(
		'firstname'=>array(),
		'lastname'=>array(),
		'display_name'=>array(),
		'date_added'=>array('type'=>'uts'),
		'last_updated'=>array('type'=>'uts'),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1005', 'msg'=>'Unable to update local user: ' . $remote_user['uuid'], 'err'=>$rc['err']));
	}
	if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
		$strsql = "UPDATE ciniki_users SET " . $rc['strsql'] . " "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_user['id']) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1006', 'msg'=>'Unable to update local user: ' . $remote_user['uuid'], 'err'=>$rc['err']));
		}
		$db_updated = 1;
	}

	//
	// Update the user history
	//
	if( isset($remote_user['history']) ) {
		if( isset($local_user['history']) ) {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, 0, 'ciniki.users',
				'ciniki_user_history', $local_user['id'], 'ciniki_users', 
				$remote_user['history'], $local_user['history'], array());
		} else {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, 0, 'ciniki.users',
				'ciniki_user_history', $local_user['id'], 'ciniki_users', 
				$remote_user['history'], array(), array());
		}
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'887', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Add to syncQueue to sync with other servers.  This allows for cascading syncs.
	//
	if( $db_updated > 0 ) {
		//
		// Get the list of users this user is part of, and replicate that user for that business
		//
		$ciniki['syncqueue'][] = array('method'=>'ciniki.users.user.push', 
			'args'=>array('id'=>$user_id, 'ignore_sync_id'=>$sync['id']));
	}

	return array('stat'=>'ok', 'id'=>$user_id);
}
?>
