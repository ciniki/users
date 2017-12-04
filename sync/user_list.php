<?php
//
// Description
// -----------
// This method will return the list of users attached to the tenant and their last_updated date.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_users_user_list($ciniki, $sync, $tnid, $args) {
    //
    // Check the args
    //
    if( !isset($args['type']) ||
        ($args['type'] != 'partial' && $args['type'] != 'full' && $args['type'] != 'incremental') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.67', 'msg'=>'No type specified'));
    }
    if( $args['type'] == 'incremental' 
        && (!isset($args['since_uts']) || $args['since_uts'] == '') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.68', 'msg'=>'No timestamp specified'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');

    //
    // Select the users who are attached to this tenant, and get the latest last_updated
    // field from ciniki_tenant_users, or ciniki_users.
    //
    $strsql = "SELECT ciniki_users.uuid, UNIX_TIMESTAMP(ciniki_users.last_updated) "
//      . "MAX(UNIX_TIMESTAMP("
//          . "IF(ciniki_tenant_users.last_updated>ciniki_users.last_updated, "
//              . "ciniki_tenant_users.last_updated, "
//              . "ciniki_users.last_updated)"
//          . ")) AS last_updated " 
        . "FROM ciniki_tenant_users, ciniki_users "
        . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_tenant_users.user_id = ciniki_users.id "
        . "GROUP BY ciniki_users.uuid "
        . "";
    if( $args['type'] == 'incremental' ) {
        $strsql .= "AND UNIX_TIMESTAMP(ciniki_users.last_updated) >= '" . ciniki_core_dbQuote($ciniki, $args['since_uts']) . "' ";
    }
    $strsql .= "ORDER BY ciniki_users.last_updated "
        . "";
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.users', 'users', 'uuid');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.69', 'msg'=>'Unable to get list', 'err'=>$rc['err']));
    }

    if( !isset($rc['users']) ) {
        $users = array();
    } else {
        $users = $rc['users'];
    }

    //
    // Note: Users are never deleted, just their status is changed
    //

    return array('stat'=>'ok', 'list'=>$users, 'deleted'=>array());
}
?>
