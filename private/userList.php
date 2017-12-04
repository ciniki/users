<?php
//
// Description
// -----------
// This method will merge in the field user_display_name when a record contains the variable user_id.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
function ciniki_users_mergeDisplayNames($ciniki, $hash) {

    if( !is_array($ids) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.9', 'msg'=>'Invalid list of users'));
    }

    //
    // Query for the tenant users
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    $strsql = "SELECT id, email, firstname, lastname, display_name, perms FROM ciniki_users "
        . "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
        . "ORDER BY lastname, firstname ";
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', $container_name, 'user', array('stat'=>'ok', $container_name=>array()));
}
?>
