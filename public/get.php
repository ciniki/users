<?php
//
// Description
// -----------
// This method will get the details for a user.
//
// Info
// ----
// publish:         yes
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:             The ID of the user to get the details for.
//
// Returns
// -------
//
function ciniki_users_get($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access, should only be accessible by sysadmin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.get', $args['user_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $date_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Get all the information form ciniki_users table
    //
    $strsql = "SELECT id, avatar_id, email, username, perms, status, timeout, "
        . "firstname, lastname, display_name, login_attempts, "
        . "DATE_FORMAT(date_added, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_added, "
        . "DATE_FORMAT(last_updated, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS last_updated, "
        . "DATE_FORMAT(last_login, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS last_login, "
        . "DATE_FORMAT(last_pwd_change, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS last_pwd_change "
        . "FROM ciniki_users "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['user']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.33', 'msg'=>'Unable to find user'));
    }
    $user = $rc['user'];

    //
    // Get all the businesses the user is a part of
    //
    $strsql = "SELECT ciniki_businesses.id, ciniki_businesses.name "
        . "FROM ciniki_business_users, ciniki_businesses "
        . "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "AND ciniki_business_users.business_id = ciniki_businesses.id "
        . "AND ciniki_business_users.status = 10 "
        . "";
    $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'businesses', 'business', array('stat'=>'ok', 'businesses'=>array()));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $user['businesses'] = $rc['businesses'];

    //
    // Get all the settings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_user_details', 'user_id', $args['user_id'], 'ciniki.users', 'details', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($rc['details']) ) {
        $user['details'] = $rc['details'];
    }

    return array('stat'=>'ok', 'user'=>$user);
}
?>
