<?php
//
// Description
// -----------
// This method checks the system to see if a username already exists.
//
// Arguments
// ---------
// api_key:
// auth_token:
// start_needle:    The string to search for a matching username.
// search_limit:    (optional) The maximum number of entries to return. 
//                  If not specified, the default limit is 11.
// 
// Returns
// -------
// <rsp stat="ok">
//      <users>
//          <user id="3421" firstname="Andrew" lastname="Rivett" display_name="Andrew R" email="andrew@ciniki.ca" />
//          ...
//      </users>
// </rsp>
//
function ciniki_users_checkUsernameAvailable($ciniki) {
    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.checkUsernameAvailable', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'username'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Username'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Query for the users with a username that starts with the 'start_needle' passed from through the API
    //
    $strsql = "SELECT id, firstname, lastname, display_name, username "
        . "FROM ciniki_users "
        . "WHERE username = '" . ciniki_core_dbQuote($ciniki, $args['username']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] == 0 ) {
        return array('stat'=>'ok', 'exists'=>'no');
    } 

    return array('stat'=>'ok', 'exists'=>'yes');
}
?>
