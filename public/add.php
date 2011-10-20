<?php
//
// Description
// -----------
// This function will add a new user.  You must be a sys admin to 
// be authorized to add a business.
//
// Info
// ----
// Status: beta
// Publish: no
//
// Arguments
// ---------
// api_key:
// auth_token:
// email.address:				The email address of user.
// user.firstname:				The firstname of user.
// user.lastname:				The lastname of the user.
// user.display_name:			The display_name for the user.
//
// Example Returns
// ---------------
// <rsp stat='ok' id='9999' />
//
function ciniki_users_add($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'email.address'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No email specified'), 
		'user.username'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No username specified'), 
		'user.firstname'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No username specified'), 
		'user.lastname'=>array('required'=>'no', 'default'=>'', 'blank'=>'no', 'errmsg'=>'No username specified'), 
		'user.display_name'=>array('required'=>'no', 'default'=>'', 'blank'=>'no', 'errmsg'=>'No username specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access, allow only sysadmins
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.add', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check if name or tagline was specified
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');

	//
	// Create a random password for the user
	//
	$password = '';
	$chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
	for($i=0;$i<8;$i++) {
		$password .= substr($chars, rand(0, strlen($chars)-1), 1);
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

	$strsql = "INSERT INTO users (date_added, email, username, firstname, lastname, display_name, "
		. "perms, status, timeout, password, last_updated) VALUES ("
		. "UTC_TIMESTAMP()" 
		. ", '" . ciniki_core_dbQuote($ciniki, $args['email.address']) . "'"
		. ", '" . ciniki_core_dbQuote($ciniki, $args['user.username']) . "'"
		. ", '" . ciniki_core_dbQuote($ciniki, $args['user.firstname']) . "'"
		. ", '" . ciniki_core_dbQuote($ciniki, $args['user.lastname']) . "'"
		. ", '" . ciniki_core_dbQuote($ciniki, $args['user.display_name']) . "'"
		. ", 0, 1, 0, SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "'), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('code'=>'332', 'msg'=>'Unable to add user', 'err'=>$rc['err']));
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('code'=>'168', 'msg'=>'Unable to add business'));
	}
	$user_id = $rc['insert_id'];
	ciniki_core_dbAddChangeLog($ciniki, 'users', 0, 'users', "$user_id", 'email', $args['email.address']);
	ciniki_core_dbAddChangeLog($ciniki, 'users', 0, 'users', "$user_id", 'username', $args['user.username']);
	ciniki_core_dbAddChangeLog($ciniki, 'users', 0, 'users', "$user_id", 'firstname', $args['user.firstname']);
	ciniki_core_dbAddChangeLog($ciniki, 'users', 0, 'users', "$user_id", 'lastname', $args['user.lastname']);
	ciniki_core_dbAddChangeLog($ciniki, 'users', 0, 'users', "$user_id", 'display_name', $args['user.display_name']);

	if( $user_id < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('code'=>'170', 'msg'=>'Unable to add user'));
	}

	//
	// Set default preferences
	//
	$default_details = array(
		'settings.date_format'=>'%b %e, %Y',
		'settings.datetime_format'=>'%b %e, %Y %l:%i %p',
		);
	foreach( $default_details as $detail_key=>$detail_value ) {
		$strsql = "INSERT INTO user_details (user_id, detail_key, detail_value, date_added, last_updated) "
			. "VALUES ('" . ciniki_core_dbQuote($ciniki, $user_id) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $detail_key) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $detail_value) . "', "
			. "UTC_TIMESTAMP(), UTC_TIMESTAMP()); "
			. "";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'users');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'users');
			return $rc;
		}
		ciniki_core_dbAddChangeLog($ciniki, 'users', 0, 'user_details', 
			$args['user_id'] . "-$detail_key", 'detail_value', $detail_value);
	}

	//
	// Check if there should be a notification sent to the user
	//
	if( isset($ciniki['request']['args']['welcome_email']) && $ciniki['request']['args']['welcome_email'] == 'yes' ) {
		//
		// FIXME: Get master business information
		//

		$subject = "Welcome to Ciniki";
		$msg = "Your account has been created, you can login at the following address:\n"
			. "\n"
			. "https://" . $_SERVER['SERVER_NAME'] . "/cinikii/\n"
			. "Username: " . $ciniki['request']['args']['user.username'] . "\n"
			. "Password: $password   ** Please change this immediately **\n"
			. "\n"
			. "\n";
		//
		// FIXME: Check if the account should be verified, and include a verification link
		// 
	
		//
		//
		// The from address can be set in the config file.
		//
		$headers = 'From: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n" .
				'Reply-To: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n" .
				'X-Mailer: PHP/' . phpversion();
		mail($args['email.address'], $subject, $msg, $headers, '-f' . $ciniki['config']['core']['system.email']);
	}

	ciniki_core_dbTransactionCommit($ciniki, 'users');
	return array('stat'=>'ok', 'id'=>$user_id);
}
?>
