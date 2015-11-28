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
// container_name:		The name for the array container for the users.
// ids:					The array of user IDs to lookup in the database.
//
// Returns
// -------
// <users>
//		<user id='1' display_name='' />
// </users>
//
function ciniki_users_hooks_lookupUser(&$ciniki, $business_id, $args) {

    if( !isset($args['id']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2761', 'msg'=>'No user specified.'));
    }

    //
    // Check the cache for a stored value
    //
    if( isset($ciniki['users'][$args['id']]) ) {
        return array('stat'=>'ok', 'user'=>$ciniki['users'][$args['id']]);
    }

	//
	// Query for the users
	//
	$strsql = "SELECT id, display_name "
        . "FROM ciniki_users "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['user']) ) {
		return array('stat'=>'noexist', 'user'=>array('id'=>0, 'display_name'=>''));
    }

    if( !isset($ciniki['users']) ) {
        $ciniki['users'] = array();
    }
    $ciniki['users'][$rc['user']['id']] = $rc['user'];

    return $rc;
}
?>
