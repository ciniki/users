<?php
//
// Description
// -----------
// This method will setup a temporary password for a user, and email 
// the temporary password to them.
//
// Info
// ----
// publish: 	yes
//
// API Arguments
// ---------
// api_key:
//
// email:			The email address of the user to reset.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_users_passwordRequestReset($ciniki) {
	
	// FIXME: Require sysadmin password to verify user before allowing a reset.

	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'email'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.passwordRequestReset', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

    //  
	// Create a random password for the user
	//  
	$password = ''; 
	$chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
	for($i=0;$i<32;$i++) {
		$password .= substr($chars, rand(0, strlen($chars)-1), 1); 
	}

	//
	// Get the username for the account
	//
	$strsql = "SELECT id, username, email, firstname, lastname, display_name FROM ciniki_users "
		. "WHERE email = '" . ciniki_core_dbQuote($ciniki, $args['email']) . "' ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'users', 'user');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'456', 'msg'=>'Unable to reset password.', 'err'=>$rc['err']));
	}
	if( !isset($rc['user']) || !isset($rc['user']['username']) || !isset($rc['user']['email']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'457', 'msg'=>'Unable to reset password.'));
	}
	$user = $rc['user'];

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

	//
	// Set the new temporary password
	//
	$strsql = "UPDATE ciniki_users SET temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "'), "
		. "temp_password_date = UTC_TIMESTAMP(), "
		. "last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'458', 'msg'=>'Unable to reset password.'));
	}

	if( $rc['num_affected_rows'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'459', 'msg'=>'Unable to reset password.'));
	}

	//
	// FIXME: Add log entry to track password changes
	//

	//
	// Email the user with the new password
	//
	if( $user['email'] != '' 
		&& isset($ciniki['config']['core']['system.email']) && $ciniki['config']['core']['system.email'] != '' ) {
		$subject = "Password reset";
		$msg = "Hi " . $user['firstname'] . ", \n\n"
			. "You have requested a new password.  "
			. "Please click the following link to reset your password.  This link will only be valid for 30 minutes.\n"
			. "\n"
			. $ciniki['config']['users']['password.forgot.url'] . "?passwordreset=$password\n"
			. "\n"
			. "\n";
		//
		// The from address can be set in the config file.
		//
		$headers = 'From: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n" .
				'Reply-To: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n" .
				'X-Mailer: PHP/' . phpversion();
		mail($user['email'], $subject, $msg, $headers, '-f' . $ciniki['config']['core']['system.email']);
	}

	//
	// Commit the changes and return
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'users');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'460', 'msg'=>'Unable to reset password.'));
	}

	//
	// Email sysadmins to let them know of a password reset request
	//
	if( isset($ciniki['config']['users']['password.forgot.notify']) && $ciniki['config']['users']['password.forgot.notify'] != '' ) {
		date_default_timezone_set('America/Eastern');
		$subject = $user['display_name'] . " forgot password";
		$msg = "Forgot password request: \n\n"
			. "user: " . $user['display_name'] . "\n"
			. "name: " . $user['firstname'] . " " . $user['lastname'] . "\n"
			. "email: " . $user['email'] . "\n"
			. "time: " . date("D M j, Y H:i e") . "\n"
			. "" . "\n";
		mail($ciniki['config']['users']['password.forgot.notify'], $subject, $msg, $headers, '-f' . $ciniki['config']['core']['system.email']);
	}

	return array('stat'=>'ok');
}
?>
