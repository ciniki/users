<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_users_sync_objects($ciniki, &$sync, $tnid, $args) {
    //
    // Note: Pass the standard set of arguments in, they may be required in the future
    //
    
    $objects = array();
    $objects['user'] = array(
        'name'=>'User', 
        'table'=>'ciniki_users',
        'fields'=>array(
            'email'=>array(),
            'firstname'=>array(),
            'lastname'=>array(),
            'display_name'=>array(),
            'timeout'=>array(),
            'avatar_id'=>array(),
//          'avatar_id'=>array('ref'=>'ciniki.images.image'),
            ),
        'history_table'=>'ciniki_user_history',
        'lookup'=>'ciniki.users.user.lookup',
        'get'=>'ciniki.users.user.get',
        'update'=>'ciniki.users.user.update',
        'list'=>'ciniki.users.user.list',
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
