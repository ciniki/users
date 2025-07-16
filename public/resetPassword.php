<?php
//
// Description
// -----------
// This function will reset a users password, if the calling user is a sysadmin.
// The new password will be set and emailed to the user.  The sysadmin does not
// have a chance to see the password.
//
// Info
// ----
// publish:         no
// 
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:         The user to have the password reset.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_users_resetPassword(&$ciniki) {
    
    // FIXME: Require sysadmin password to verify user before allowing a reset.

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.resetPassword', $args['user_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //  
    // Create a random password for the user
    //  
    $password = ''; 
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    for($i=0;$i<8;$i++) {
        $password .= substr($chars, rand(0, strlen($chars)-1), 1); 
    }

    //
    // Get the username for the account
    //
    $strsql = "SELECT username, email FROM ciniki_users "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.42', 'msg'=>'Unable to reset password.', 'err'=>$rc['err']));
    }
    if( !isset($rc['user']) || !isset($rc['user']['username']) || !isset($rc['user']['email']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.43', 'msg'=>'Unable to reset password.'));
    }
    $user = $rc['user'];

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

    //
    // Update the password, but only if the old one matches
    //
    $strsql = "UPDATE ciniki_users SET password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "') "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.44', 'msg'=>'Unable to reset password.'));
    }

    if( $rc['num_affected_rows'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.45', 'msg'=>'Unable to reset password.'));
    }

    //
    // FIXME: Add log entry to track password changes
    //

    //
    // Email the user with the new password
    //
    if( $user['email'] != '' 
        && isset($ciniki['config']['core']['system.email']) && $ciniki['config']['core']['system.email'] != '' ) {
        $subject = "Ciniki - Password reset";
        $msg = "The password for your account has been reset, please login and change your password.\n"
            . "\n"
            . "https://" . $_SERVER['SERVER_NAME'] . "/\n"
            . "Username: " . $user['username'] . "\n"
            . "Temporary Password: $password   ** Please change this immediately **\n"
            . "\n"
            . "\n";
        //
        // The from address can be set in the config file.
        //
        $ciniki['emailqueue'][] = array('user_id'=>$args['user_id'],
            'subject'=>$subject,
            'textmsg'=>$msg,
            );
//      $headers = 'From: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n" .
//              'Reply-To: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n" .
//              'X-Mailer: PHP/' . phpversion();
//      mail($user['email'], $subject, $msg, $headers, '-f' . $ciniki['config']['core']['system.email']);
    }

    //
    // Commit the changes and return
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.46', 'msg'=>'Unable to reset password.'));
    }

    return array('stat'=>'ok');
}
?>
