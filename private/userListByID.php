<?php
//
// Description
// -----------
// This function will return the list of users based on a list of IDs.  The returned
// list will be sorted by ID for each lookup.
//
// Arguments
// ---------
// ciniki:
// container_name:      The name of the container to store the user list in.
// ids:                 The user IDs to be looked up in the database.
//
// Returns
// -------
// <users>
//      <1 id='1' email='' firstname='' lastname='' display_name='' perms=''/>
// </users>
//
function ciniki_users_userListByID($ciniki, $container_name, $ids, $fields) {

    if( !is_array($ids) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.10', 'msg'=>'Invalid list of users'));
    }

    if( count($ids) < 1 ) {
        return array('stat'=>'ok', 'users'=>array());
    }

    //
    // Query for the tenant users
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    if( $fields == 'all' ) {
        $strsql = "SELECT id, email, firstname, lastname, display_name, perms FROM ciniki_users ";
    } elseif( $fields == 'display_name' ) {
        $strsql = "SELECT id, display_name FROM ciniki_users ";
    }
    $strsql .= "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, array_unique($ids)) . ") "
        . "ORDER BY id ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.users', $container_name, 'id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( in_array('-1', $ids) ) {
        $rc[$container_name]['-1'] = array('display_name'=>'Paypal IPN');
    }
    if( in_array('-2', $ids) ) {
        $rc[$container_name]['-2'] = array('display_name'=>'Website');
    }
    if( in_array('-3', $ids) ) {
        $rc[$container_name]['-3'] = array('display_name'=>'Ciniki Robot');
    }
    return $rc;
}
?>
