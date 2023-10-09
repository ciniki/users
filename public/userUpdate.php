<?php
//
// Description
// -----------
// This method will update details for a user.
//
// Info
// ----
// publish:         yes
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_users_userUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        'firstname'=>array('required'=>'no', 'blank'=>'no', 'name'=>'First Name'), 
        'lastname'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Last Name'), 
        'display_name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Display Name'), 
        'username'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Username'), 
        'email'=>array('required'=>'no', 'blank'=>'no', 'name'=>'email'), 
        'timeout'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Timeout'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.userUpdate', $args['user_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

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
    // Check username does not already exist
    //
    if( isset($args['username']) && $args['username'] != '' ) {
        $strsql = "SELECT id "
            . "FROM ciniki_users "
            . "WHERE username = '" . ciniki_core_dbQuote($ciniki, $args['username']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.52', 'msg'=>'Username already taken'));
        }
    }

    //
    // Check the email doesn't exist
    //
    if( isset($args['email']) && $args['email'] != '' ) {
        $strsql = "SELECT id "
            . "FROM ciniki_users "
            . "WHERE email = '" . ciniki_core_dbQuote($ciniki, $args['email']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.53', 'msg'=>'Email already taken'));
        }
    }

    //
    // Check if name or tagline was specified
    //
    $fields = array('firstname', 'lastname', 'display_name', 'username', 'email', 'timeout');

    $strsql = "";
    foreach($fields as $field) {
        if( isset($args[$field]) ) {
            $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 2, 'ciniki_users', 
                $args['user_id'], $field, $args[$field]);
        }
    }
    //
    // Always update at least the last_updated field so it will be transfered with sync
    //
    $strsql = "UPDATE ciniki_users SET last_updated = UTC_TIMESTAMP()" . $strsql 
        . " WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.54', 'msg'=>'Unable to update user', 'err'=>$rc['err']));
    }
    
    //
    // Allowed user detail keys 
    //
    $allowed_keys = array(
        'settings.time_format',
        'settings.date_format',
        'settings.datetime_format',
        'ui-history-date-display',
        );
    foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
        if( in_array($arg_name, $allowed_keys) ) {
            $strsql = "INSERT INTO ciniki_user_details (user_id, detail_key, detail_value, date_added, last_updated) "
                . "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $arg_name) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $arg_value) . "', "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $arg_value) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
                return $rc;
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 2, 'ciniki_user_details', $args['user_id'], $arg_name, $arg_value);
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.55', 'msg'=>'Unable to update user detail', 'err'=>$rc['err']));
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');

    //
    // Update the last_updated of the user
    //
    $strsql = "UPDATE ciniki_users SET last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of tenants this user is part of, and replicate that user for that tenant
    //
    $strsql = "SELECT id, tnid "
        . "FROM ciniki_tenant_users "
        . "WHERE ciniki_tenant_users.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenants = $rc['rows'];
    foreach($tenants as $rid => $row) {
        ciniki_tenants_updateModuleChangeDate($ciniki, $row['tnid'], 'ciniki', 'users');
        $ciniki['syncqueue'][] = array('push'=>'ciniki.tenants.user', 
            'args'=>array('id'=>$row['id']));
    }

    return array('stat'=>'ok');
}
?>
