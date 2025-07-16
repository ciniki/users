<?php
//
// Description
// -----------
// This method will setup a temporary password for a user, and email 
// the temporary password to them.
//
// Info
// ----
// publish:     yes
//
// API Arguments
// ---------
// api_key:
//
// email:           The email address of the user to reset.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_users_passwordRequestReset(&$ciniki) {
    
    // FIXME: Require sysadmin password to verify user before allowing a reset.

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'email'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
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
    $strsql = "SELECT id, username, email, firstname, lastname, display_name "
        . "FROM ciniki_users "
        . "WHERE email = '" . ciniki_core_dbQuote($ciniki, $args['email']) . "' ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.36', 'msg'=>'Unable to reset password.', 'err'=>$rc['err']));
    }
    if( !isset($rc['user']) || !isset($rc['user']['username']) || !isset($rc['user']['email']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.37', 'msg'=>'Unable to reset password.'));
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
    // Set the new temporary password
    //
    $strsql = "UPDATE ciniki_users SET temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "'), "
        . "temp_password_date = UTC_TIMESTAMP(), "
        . "last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.38', 'msg'=>'Unable to reset password.'));
    }

    if( $rc['num_affected_rows'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.39', 'msg'=>'Unable to reset password.'));
    }

    //
    // FIXME: Add log entry to track password changes
    //

    //
    // Email the user with the new password
    //
    if( $user['email'] != '' 
        && isset($ciniki['config']['ciniki.core']['system.email']) && $ciniki['config']['ciniki.core']['system.email'] != '' ) {
        $subject = "Password reset";
        $htmlmsg = "Hi " . $user['firstname'] . ", <br/><br/>"
            . "You have requested a new password.  "
            . "Please click the following link to reset your password.  This link will only be valid for 30 minutes.<br/>"
            . "<br/>"
            . "<a href='" . $ciniki['config']['ciniki.users']['password.forgot.url'] . "?passwordreset=$password'>Reset Password</a><br/>"
            . "<br/>"
            . "If that doesn't work, click this link: <br/>"
            . $ciniki['config']['ciniki.users']['password.forgot.url'] . "?passwordreset=$password<br/>"
            . "<br/";
        $msg = "Hi " . $user['firstname'] . ", \n\n"
            . "You have requested a new password.  "
            . "Please click the following link to reset your password.  This link will only be valid for 30 minutes.\n"
            . "\n"
            . $ciniki['config']['ciniki.users']['password.forgot.url'] . "?passwordreset=$password\n"
            . "\n"
            . "\n";
        //
        // The from address can be set in the config file.
        //
        $ciniki['emailqueue'][] = array('user_id'=>$user['id'],
            'subject'=>$subject,
            'textmsg'=>$msg,
            'htmlmsg'=>$htmlmsg,
            );
    }

    //
    // Commit the changes and return
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.40', 'msg'=>'Unable to reset password.'));
    }

    //
    // Email sysadmins to let them know of a password reset request
    //
    if( isset($ciniki['config']['ciniki.users']['password.forgot.notify']) && $ciniki['config']['ciniki.users']['password.forgot.notify'] != '' ) {
        date_default_timezone_set('UTC');
        $subject = $user['display_name'] . " forgot password";
        $msg = "Forgot password request: \n\n"
            . "user: " . $user['display_name'] . "\n"
            . "name: " . $user['firstname'] . " " . $user['lastname'] . "\n"
            . "email: " . $user['email'] . "\n"
            . "time: " . date("D M j, Y H:i e") . "\n"
            . "" . "\n";
        $ciniki['emailqueue'][] = array('to'=>$ciniki['config']['ciniki.users']['password.forgot.notify'],
            'subject'=>$subject,
            'textmsg'=>$msg,
            );
    }

    return array('stat'=>'ok');
}
?>
