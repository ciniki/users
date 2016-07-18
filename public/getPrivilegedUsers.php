<?php
//
// Description
// -----------
// This function will get all users who have elevated priviledges.  They
// can be sysadmins or business owners.
//
// Info
// ----
// publish:         no
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <users>
//      <user id='1' email='' firstname='' lastname='' display_name='' perms=''/>
// </users>
//
function ciniki_users_getPrivilegedUsers($ciniki) {
    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.getPrivilegedUsers', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Query for the business users
    //
    $strsql = "SELECT id, email, firstname, lastname, display_name, perms FROM ciniki_users "
        . "WHERE perms <> 0 ORDER BY lastname, firstname ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
}
?>
