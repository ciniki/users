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
function ciniki_users_emailUser($ciniki, $user_id, $subject, $msg) {

	//
	// Query for user information
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$strsql = "SELECT id, firstname, lastname, email "
		. "FROM users "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $user_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'users', 'user');
	if( $rc['stat'] != 'ok' || !isset($rc['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'248', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
	}
	$user = $rc['user'];

	//  
	// The from address can be set in the config file.
	//  
	$headers = 'From: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n" .
		'Reply-To: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n" .
		'X-Mailer: PHP/' . phpversion();
	mail($user['email'], $subject, $msg, $headers, '-f' . $ciniki['config']['core']['system.email']);

	return array('stat'=>'ok');
}
?>
