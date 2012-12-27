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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$strsql = "SELECT id, firstname, lastname, email "
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
	$headers = 'From: "' . $ciniki['config']['ciniki.core']['system.email.name'] . '" <' . $ciniki['config']['ciniki.core']['system.email'] . ">\r\n" .
		'Reply-To: "' . $ciniki['config']['ciniki.core']['system.email.name'] . '" <' . $ciniki['config']['ciniki.core']['system.email'] . ">\r\n" .
		'X-Mailer: PHP/' . phpversion();
	mail($user['email'], $subject, $msg, $headers, '-f' . $ciniki['config']['ciniki.core']['system.email']);

	return array('stat'=>'ok');
}
?>
