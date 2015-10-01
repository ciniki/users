<?php
//
// Description
// -----------
// This function will send an email to a user of the system.
// 
// Info
// ----
// Status: beta
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_users_emailUser($ciniki, $business_id, $user_id, $email) {

	//
	// Query for user information
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$strsql = "SELECT id, CONCAT_WS(' ', firstname, lastname) AS name, email "
		. "FROM ciniki_users "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $user_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
	if( $rc['stat'] != 'ok' || !isset($rc['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'248', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
	}
	$user = $rc['user'];

	//  
	// The from address can be set in the config file.
	//  
//	$headers = 'From: "' . $ciniki['config']['ciniki.core']['system.email.name'] . '" <' . $ciniki['config']['ciniki.core']['system.email'] . ">\r\n" .
//		'Reply-To: "' . $ciniki['config']['ciniki.core']['system.email.name'] . '" <' . $ciniki['config']['ciniki.core']['system.email'] . ">\r\n" .
//		'X-Mailer: PHP/' . phpversion();
//	mail($user['email'], $subject, $msg, $headers, '-f' . $ciniki['config']['ciniki.core']['system.email']);

	require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.phpmailer.php');
	require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.smtp.php');

	$mail = new PHPMailer;

	$mail->IsSMTP();

	$use_config = 'yes';
	if( $business_id > 0 ) { 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
		$rc = ciniki_mail_getSettings($ciniki, $business_id);
		if( $rc['stat'] == 'ok' && isset($rc['settings'])
			&& isset($rc['settings']['smtp-servers']) && $rc['settings']['smtp-servers'] != ''
			&& isset($rc['settings']['smtp-username']) && $rc['settings']['smtp-username'] != ''
			&& isset($rc['settings']['smtp-password']) && $rc['settings']['smtp-password'] != ''
			&& isset($rc['settings']['smtp-secure']) && $rc['settings']['smtp-secure'] != ''
			&& isset($rc['settings']['smtp-port']) && $rc['settings']['smtp-port'] != ''
			) {
			$mail->Host = $rc['settings']['smtp-servers'];
			$mail->SMTPAuth = true;
			$mail->Username = $rc['settings']['smtp-username'];
			$mail->Password = $rc['settings']['smtp-password'];
			$mail->SMTPSecure = $rc['settings']['smtp-secure'];
			$mail->Port = $rc['settings']['smtp-port'];
			$use_config = 'no';

			if( isset($rc['settings']['smtp-secure']) && $rc['settings']['smtp-secure'] != ''
				&& isset($rc['settings']['smtp-port']) && $rc['settings']['smtp-port'] != '' ) {
				$mail->From = $rc['settings']['smtp-from-address'];
				$mail->FromName = $rc['settings']['smtp-from-name'];
			} else {
				$mail->From = $ciniki['config']['ciniki.core']['system.email'];
				$mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
			}
		}
	} 
	
	//
	// If not enough informatio, or none provided, default back to system email
	//
	if( $use_config == 'yes' ) {
		$mail->Host = $ciniki['config']['ciniki.core']['system.smtp.servers'];
		$mail->SMTPAuth = true;
		$mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
		$mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];
		$mail->SMTPSecure = $ciniki['config']['ciniki.core']['system.smtp.secure'];
		$mail->Port = $ciniki['config']['ciniki.core']['system.smtp.port'];

		$mail->From = $ciniki['config']['ciniki.core']['system.email'];
		$mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
	}

	$mail->AddAddress($user['email'], $user['name']);

	// Add reply to if specified
	if( isset($email['replyto_email']) && $email['replyto_email'] != '' ) {
		if( isset($email['replyto_name']) && $email['replyto_name'] != '' ) {
			$mail->addReplyTo($email['replyto_email'], $email['replyto_name']);
		} else {
			$mail->addReplyTo($email['replyto_email']);
		}
	}

	if( isset($email['htmlmsg']) && $email['htmlmsg'] != '' ) {
		$mail->IsHTML(true);
		$mail->Subject = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['subject']);
		$mail->Body = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['htmlmsg']);
		$mail->AltBody = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['textmsg']);
	} else {
		$mail->IsHTML(false);
		$mail->Subject = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['subject']);
		$mail->Body = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['textmsg']);
	}

	if( !$mail->Send() ) {
		error_log("MAIL-ERR: [" . $user['email'] . "] " . $mail->ErrorInfo);
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'56', 'msg'=>'Unable to send email'));
	}

	return array('stat'=>'ok');
}
?>
