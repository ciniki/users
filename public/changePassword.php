<?php
//
// Description
// -----------
// This method will change a users password.  The user must provide their old password
// as verification to change to a new password.
//
// Info
// ----
// publish:         yes
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// oldpassword:     The old password for the user.  Done as a security measure so,
//                  if somebody hijacks the session, they can't change the password.
//
// newpassword:     The new password for the user.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_users_changePassword($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'oldpassword'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Old Password'), 
        'newpassword'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'New Password'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    if( strlen($args['newpassword']) < 8 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'121', 'msg'=>'New password must be longer than 8 characters.'));
    }

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.changePassword', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check old password
    //
    $strsql = "SELECT id, email FROM ciniki_users "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['oldpassword']) . "') ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Perform an extra check to make sure only 1 row was found, other return error
    //
    if( $rc['num_rows'] != 1 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'122', 'msg'=>'Invalid old password'));
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

    //
    // Update the password, but only if the old one matches
    //
    $strsql = "UPDATE ciniki_users SET password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['newpassword']) . "'), "
        . "last_updated = UTC_TIMESTAMP(), "
        . "last_pwd_change = UTC_TIMESTAMP() "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['oldpassword']) . "') ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'335', 'msg'=>'Unable to update password.'));
    }

    if( $rc['num_affected_rows'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'123', 'msg'=>'Unable to change password.'));
    }

    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'336', 'msg'=>'Unable to update password.'));
    }

    return array('stat'=>'ok');
}
?>
