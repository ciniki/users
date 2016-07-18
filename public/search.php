<?php
//
// Description
// -----------
// This method will search the existing users for an email.
//
// Info
// ----
// publish:         no
//
// Arguments
// ---------
// api_key:
// auth_token:
// start_needle:    The string to search for a matching email.
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
function ciniki_users_search($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'limit'=>array('required'=>'no', 'default'=>'11', 'blank'=>'yes', 'name'=>'Limit'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.search', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Query for the user by email
    //
    $strsql = "SELECT id, firstname, lastname, username, display_name, email "
        . "FROM ciniki_users "
        . "WHERE firstname LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR lastname LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR username LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
}
?>
