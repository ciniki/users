<?php
//
// Description
// -----------
// This method will retrieve only the users who are marked as sys admins.
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
//      <user id='1' email='' firstname='' lastname='' display_name='' />
// </users>
//
function ciniki_users_getSysAdmins($ciniki) {
    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.getSysAdmins', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Query for the tenant users
    //
    $strsql = "SELECT id, email, firstname, lastname, display_name FROM ciniki_users "
        . "WHERE (perms & 0x01) = 0x01 ORDER BY lastname, firstname ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
}
?>
