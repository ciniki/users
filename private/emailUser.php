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
function ciniki_users_emailUser($ciniki, $user_id, $subject, $msg, $html_msg) {

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
	$mail->Host = $ciniki['config']['ciniki.core']['system.smtp.servers'];
	$mail->SMTPAuth = true;
	$mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
	$mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];
	$mail->SMTPSecure = $ciniki['config']['ciniki.core']['system.smtp.secure'];
	$mail->Port = $ciniki['config']['ciniki.core']['system.smtp.port'];

	$mail->From = $ciniki['config']['ciniki.core']['system.email'];
	$mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
	$mail->AddAddress($user['email'], $user['name']);

	if( isset($html_msg) && $html_msg != '' ) {
		$mail->IsHTML(true);
		$mail->Subject = $subject;
		$mail->Body = $html_msg;
		$mail->AltBody = $msg;
	} else {
		$mail->IsHTML(false);
		$mail->Subject = $subject;
		$mail->Body = $msg;
	}

	if( !$mail->Send() ) {
		error_log("MAIL-ERR: [" . $user['email'] . "] " . $mail->ErrorInfo);
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'56', 'msg'=>'Unable to send email'));
	}

	return array('stat'=>'ok');
}
?>
