<?php
//
// Description
// -----------
// This function will check if the user has access to a specified module and function.
//
// Info
// ----
// Status:      beta
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_users_checkAccess($ciniki, $business_id, $method, $user_id) {
    //
    // Methods which don't require authentication
    //
    $noauth_methods = array(
        'ciniki.users.auth',
        'ciniki.users.passwordRequestReset',
        'ciniki.users.changeTempPassword',
        );
    if( in_array($method, $noauth_methods) ) {
        return array('stat'=>'ok');
    }


    //
    // Check the user is authenticated
    //
    if( !isset($ciniki['session'])
        || !isset($ciniki['session']['user'])
        || !isset($ciniki['session']['user']['id'])
        || $ciniki['session']['user']['id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.5', 'msg'=>'User not authenticated'));
    }

    //
    // Check if the requested method is a public method
    //
    $public_methods = array(
        'ciniki.users.changePassword',
        'ciniki.users.logout',
        'ciniki.users.formatDate',
        );
    if( in_array($method, $public_methods) ) {
        return array('stat'=>'ok');
    }

    //
    // If the user is a sysadmin, they have access to all functions
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok');
    }

    //
    // Some methods are allowed for business owners to add users to their business
    //
    $business_owner_methods = array(
        'ciniki.users.add',
        );
    if( in_array($method, $business_owner_methods) ) {
        //
        // Check if the requesting user is the business owner
        //
        $strsql = "SELECT business_id, user_id "
            . "FROM ciniki_business_users "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND package = 'ciniki' "
            . "AND status = 10 "
            . "AND (permission_group = 'owners' ) "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.6', 'msg'=>'Access denied', 'err'=>$rc['err']));
        }

        //
        // If the user has permission, return ok
        //
        if( isset($rc['user']) && $rc['user']['business_id'] == $business_id && $rc['user']['user_id'] == $ciniki['session']['user']['id'] ) {
            return array('stat'=>'ok');
        }
        
    }

    //
    // Some methods should be available to both sys admins, and the user.  Verify
    // the user requesting the change is the same as the user to be changed.
    //
    $user_methods = array(
        'ciniki.users.avatarDelete',
        'ciniki.users.getDetailHistory',
        'ciniki.users.getDetails',
        'ciniki.users.avatarGet',
        'ciniki.users.get',
        'ciniki.users.avatarSave',
        'ciniki.users.updateDetails',
        );
    if( in_array($method, $user_methods) && $user_id > 0 && $ciniki['session']['user']['id'] == $user_id ) {
        return array('stat'=>'ok');
    }

    //
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.7', 'msg'=>'Access denied'));
}
?>
