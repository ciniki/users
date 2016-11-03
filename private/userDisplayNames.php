<?php
//
// Description
// -----------
// This function will return a list of user display names, which can be returned
// via the API to an end user.  No email addresses or permissions will be returned.
//
// Arguments
// ---------
// ciniki:
// container_name:      The name for the array container for the users.
// ids:                 The array of user IDs to lookup in the database.
//
// Returns
// -------
// <users>
//      <user id='1' display_name='' />
// </users>
//
function ciniki_users_userDisplayNames($ciniki, $container_name, $ids) {

    if( !is_array($ids) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.8', 'msg'=>'Invalid list of users'));
    }

    //
    // Query for the users
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    $strsql = "SELECT id, display_name FROM ciniki_users "
        . "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, array_unique($ids)) . ") "
        . "ORDER BY lastname, firstname ";
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', $container_name, 'user', array('stat'=>'ok', $container_name=>array()));
}
?>
