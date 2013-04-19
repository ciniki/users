<?php
//
// Description
// -----------
// This method will add a new user to the system.  Only sysadmins
// or business owners have access to add a user.
//
// Info
// ----
// publish:			no
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:					The ID of the business to add the user to.  If the requesting
//								user is not a sysadmin but the owner of the business they
//								are allowed to add a user.
// email.address:				The email address of user.
// user.username:				The usernamed for the new user.
// user.firstname:				The firstname of user.
// user.lastname:				(optional) The lastname of the user.
// user.display_name:			(optional) The display_name for the user.
//
// Example Returns
// ---------------
// <rsp stat='ok' id='213' />
//
function ciniki_users_add(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'email.address'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Email Address'), 
		'user.username'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'username'), 
		'user.firstname'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Firstname'), 
		'user.lastname'=>array('required'=>'no', 'default'=>'', 'blank'=>'no', 'name'=>'Lastname'), 
		'user.display_name'=>array('required'=>'no', 'default'=>'', 'blank'=>'no', 'name'=>'Display Name'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access, allow only sysadmins
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, $args['business_id'], 'ciniki.users.add', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check if name or tagline was specified
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

	//
	// Create a random password for the user
	//
	$password = '';
	$temp_password = '';
	$chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
	for($i=0;$i<8;$i++) {
		$password .= substr($chars, rand(0, strlen($chars)-1), 1);
	}
	for($i=0;$i<16;$i++) {
		$temp_password .= substr($chars, rand(0, strlen($chars)-1), 1);
	}

	// 
	// Check if email address already exist, and just add the user
	//
	$strsql = "SELECT id, email "
		. "FROM ciniki_users "
		. "WHERE email = '" . ciniki_core_dbQuote($ciniki, $args['email.address']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	// If a users was found, add to business
	if( isset($rc['user']) ) {
		return array('stat'=>'ok', 'id'=>$rc['user']['id']);
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

	$strsql = "INSERT INTO ciniki_users (uuid, date_added, email, username, firstname, lastname, display_name, "
		. "perms, status, timeout, password, temp_password, temp_password_date, last_updated) VALUES ("
		. "UUID(), "
		. "UTC_TIMESTAMP()" 
		. ", '" . ciniki_core_dbQuote($ciniki, $args['email.address']) . "'"
		. ", '" . ciniki_core_dbQuote($ciniki, $args['user.username']) . "'"
		. ", '" . ciniki_core_dbQuote($ciniki, $args['user.firstname']) . "'"
		. ", '" . ciniki_core_dbQuote($ciniki, $args['user.lastname']) . "'"
		. ", '" . ciniki_core_dbQuote($ciniki, $args['user.display_name']) . "'"
		. ", 0, 1, 0, "
		. "SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "'), "
		. "SHA1('" . ciniki_core_dbQuote($ciniki, $temp_password) . "'), "
		. "UTC_TIMESTAMP(), "
		. "UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'332', 'msg'=>'Unable to add user', 'err'=>$rc['err']));
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'168', 'msg'=>'Unable to add business'));
	}
	$user_id = $rc['insert_id'];
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 
		1, 'ciniki_users', $user_id, 'email', $args['email.address']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 
		1, 'ciniki_users', $user_id, 'username', $args['user.username']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 
		1, 'ciniki_users', $user_id, 'firstname', $args['user.firstname']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 
		1, 'ciniki_users', $user_id, 'lastname', $args['user.lastname']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 
		1, 'ciniki_users', $user_id, 'display_name', $args['user.display_name']);

	if( $user_id < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'170', 'msg'=>'Unable to add user'));
	}

	//
	// Set default preferences
	//
	$default_details = array(
		'settings.date_format'=>'%b %e, %Y',
		'settings.datetime_format'=>'%b %e, %Y %l:%i %p',
		);
	foreach( $default_details as $detail_key=>$detail_value ) {
		$strsql = "INSERT INTO ciniki_user_details (user_id, detail_key, detail_value, date_added, last_updated) "
			. "VALUES ('" . ciniki_core_dbQuote($ciniki, $user_id) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $detail_key) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $detail_value) . "', "
			. "UTC_TIMESTAMP(), UTC_TIMESTAMP()); "
			. "";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
			return $rc;
		}
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 
			1, 'ciniki_user_details', $user_id, $detail_key, $detail_value);
	}

	//
	// Check if there should be a notification sent to the user
	//
	if( isset($ciniki['request']['args']['welcome_email']) && $ciniki['request']['args']['welcome_email'] == 'yes' ) {
		//
		// FIXME: Get master business information
		//

		$subject = "Welcome to Ciniki";
		// $msg = "Your account has been created, you can login at the following address:\n"
		$msg = "Your account has been created, but first you must setup your password.  Please click the following "
			. "link and choose a new password.  For security purposes, this link will only remain active for 30 minutes. "
			. "If you are unable to connect in that time, please follow the link and click Send New Activation.\n"
			. "\n"
			// . "https://" . $_SERVER['SERVER_NAME'] . "/\n"
			. $ciniki['config']['ciniki.users']['password.forgot.url'] . "?email=" . urlencode($args['email.address']) . "&p=$temp_password\n"
			//. "Username: " . $ciniki['request']['args']['user.username'] . "\n "
			//. "Temporary Password: $password (This password expires in 30 minutes)\n"
			. "\n"
			. "\n";
		//
		// FIXME: Check if the account should be verified, and include a verification link
		// 
	
		//
		//
		// The from address can be set in the config file.
		//
		$ciniki['emailqueue'][] = array('to'=>$args['email.address'],
			'subject'=>$subject,
			'textmsg'=>$msg,
			);
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'365', 'msg'=>'Unable to update password.'));
	}

	return array('stat'=>'ok', 'id'=>$user_id);
}
?>
