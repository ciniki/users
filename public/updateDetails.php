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
// api_key:
// auth_token:
// user_id:                 The ID of the user to update the details for.
// user.firstname:          (optional) The new firstname for the user.
// user.lastname:           (optional) The new lastname for the user.
// user.display_name:       (optional) The new display_name for the user.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_users_updateDetails(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        'user.firstname'=>array('required'=>'no', 'blank'=>'no', 'name'=>'First Name'), 
        'user.lastname'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Last Name'), 
        'user.display_name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Display Name'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.updateDetails', $args['user_id']);
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
    // Check if name or tagline was specified
    //
    $strsql = "";
    if( isset($args['user.firstname']) && $args['user.firstname'] != '' ) {
        $strsql .= ", firstname = '" . ciniki_core_dbQuote($ciniki, $args['user.firstname']) . "'";
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 2, 'ciniki_users', $args['user_id'], 'firstname', $args['user.firstname']);
    }
    if( isset($args['user.lastname']) && $args['user.lastname'] != '' ) {
        $strsql .= ", lastname = '" . ciniki_core_dbQuote($ciniki, $args['user.lastname']) . "'";
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 2, 'ciniki_users', $args['user_id'], 'lastname', $args['user.lastname']);
    }
    if( isset($args['user.display_name']) && $args['user.display_name'] != '' ) {
        $strsql .= ", display_name = '" . ciniki_core_dbQuote($ciniki, $args['user.display_name']) . "'";
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.users', 'ciniki_user_history', 0, 2, 'ciniki_users', $args['user_id'], 'display_name', $args['user.display_name']);
    }
    //
    // Always update at least the last_updated field so it will be transfered with sync
    //
    $strsql = "UPDATE ciniki_users SET last_updated = UTC_TIMESTAMP()" . $strsql 
        . " WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.50', 'msg'=>'Unable to add user', 'err'=>$rc['err']));
    }

    //
    // Allowed user detail keys 
    //
    $allowed_keys = array(
        'settings.time_format',
        'settings.date_format',
        'settings.datetime_format',
        'settings.temperature_units',
        'settings.windspeed_units',
        'ui-history-date-display',
        'ui-calendar-view',
        'ui-calendar-remember-date',
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
    // Update session values
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryHash');
    $rc = ciniki_core_dbDetailsQueryHash($ciniki, 'ciniki_user_details', 'user_id', $ciniki['session']['user']['id'], 'settings', 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
        return $rc;
    }
    if( isset($rc['details']['settings']) && $rc['details']['settings'] != null ) {
        $ciniki['session']['user']['settings'] = $rc['details']['settings'];
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.51', 'msg'=>'Unable to update user detail', 'err'=>$rc['err']));
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
