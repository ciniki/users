<?php
//
// Description
// -----------
// This method will lookup a user_id in the database, and return the uuid
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_users_user_lookup(&$ciniki, &$sync, $business_id, $args) {
    //
    // Check the args
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    //
    // Look for the user based on the UUID, and if not found make a request to
    // add from remote side
    //
    if( isset($args['remote_uuid']) && $args['remote_uuid'] != '' ) {
        // Check if the uuid exists in the uuidmaps
        if( isset($sync['uuidmaps']['ciniki_users'][$args['remote_uuid']]) ) {
            return array('stat'=>'ok', 'id'=>$sync['uuidmaps']['ciniki_users'][$args['remote_uuid']]);
        }
        $strsql = "SELECT DISTINCT ciniki_users.id FROM ciniki_users " //, ciniki_business_users "
            . "WHERE ciniki_users.uuid = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
//          . "AND ciniki_users.id = ciniki_business_users.user_id "
//          . "AND ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1021', 'msg'=>"Unable to get the user id", 'err'=>$rc['err']));
        }
        if( isset($rc['user']) ) {
            return array('stat'=>'ok', 'id'=>$rc['user']['id']);
        }
        
        //
        // If the id was not found in the users table, try looking up in the history
        //
        $strsql = "SELECT table_key FROM ciniki_user_history "
            . "WHERE action = 1 "
            . "AND table_name = 'ciniki_users' "
            . "AND new_value = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
            . "AND table_field = 'uuid' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1022', 'msg'=>'Unable to get user id from history', 'err'=>$rc['err']));
        }
        if( isset($rc['user']) ) {
            return array('stat'=>'ok', 'id'=>$rc['user']['table_key']);
        }

        //
        // Check to see if it exists on the remote side, and add customer if necessary
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
        $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.users.user.get', 'uuid'=>$args['remote_uuid']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1023', 'msg'=>'Unable to get user from remote server', 'err'=>$rc['err']));
        }

        if( isset($rc['object']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'sync', 'user_update');
            $rc = ciniki_users_user_update($ciniki, $sync, $business_id, array('object'=>$rc['object']));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1024', 'msg'=>'Unable to add user to local server', 'err'=>$rc['err']));
            }
            return array('stat'=>'ok', 'id'=>$rc['id']);
        }

        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1048', 'msg'=>'Unable to find user'));
    }

    //
    // If requesting the local_id, the lookup in local database, don't bother with remote,
    // ID won't be there.
    //
    elseif( isset($args['local_id']) && $args['local_id'] != '' ) {
        $strsql = "SELECT DISTINCT ciniki_users.uuid FROM ciniki_users " //, ciniki_business_users "
            . "WHERE ciniki_users.id = '" . ciniki_core_dbQuote($ciniki, $args['local_id']) . "' "
//          . "AND ciniki_users.id = ciniki_business_users.user_id "
//          . "AND ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1046', 'msg'=>"Unable to get the user uuid", 'err'=>$rc['err']));
        }
        if( isset($rc['user']) ) {
            return array('stat'=>'ok', 'uuid'=>$rc['user']['uuid']);
        }
        
        //
        // If the id was not found in the users table, try looking up in the history from when it was added
        //
        $strsql = "SELECT new_value FROM ciniki_user_history "
            . "WHERE action = 1 "
            . "AND table_name = 'ciniki_customers' "
            . "AND table_key = '" . ciniki_core_dbQuote($ciniki, $args['local_id']) . "' "
            . "AND table_field = 'uuid' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1047', 'msg'=>'Unable to get user id from history', 'err'=>$rc['err']));
        }
        if( isset($rc['user']) ) {
            return array('stat'=>'ok', 'uuid'=>$rc['user']['new_value']);
        }
        
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1049', 'msg'=>'Unable to find user'));
    }

    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1020', 'msg'=>'No user specified'));
}
?>
