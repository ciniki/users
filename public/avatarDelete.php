<?php
//
// Description
// -----------
// This method will remove the users avatar.
//
// Info
// ----
// publish:         yes
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:         The ID of the user to remove the avatar for.
// 
// Example Return
// --------------
// <rsp stat="ok" />
//
function ciniki_users_avatarDelete(&$ciniki) {
    //
    // Check args
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.avatarDelete', $args['user_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) { 
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.16', 'msg'=>'Internal Error', 'err'=>$rc['err']));
    }   

    //
    // The image type will be checked in the insertFromUpload method to ensure it is an image
    // in a format we accept
    //

    //
    // Remove existing avatar
    //
    $strsql = "SELECT avatar_id FROM ciniki_users WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
        return $rc;
    }
    $avatar_id = $rc['user']['avatar_id'];

    if( $avatar_id > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'removeImage');
        $rc = ciniki_images_removeImage($ciniki, 0, $args['user_id'], $avatar_id);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
            return $rc;
        }
    }

    //
    // Update user with new image id
    //
    $strsql = "UPDATE ciniki_users SET avatar_id = 0, last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Update the session variable, if same user who's logged in
    //
    if( $ciniki['session']['user']['id'] == $args['user_id'] ) {
        $ciniki['session']['user']['avatar_id'] = 0;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.17', 'msg'=>'Unable to delete avatar', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok');
}
?>
