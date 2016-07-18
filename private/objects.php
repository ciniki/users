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
function ciniki_users_objects($ciniki) {
    $objects = array();
    $objects['user'] = array(
        'name'=>'User', 
        'table'=>'ciniki_users',
        'fields'=>array(
            'username'=>array(),
            'email'=>array(),
            'firstname'=>array(),
            'lastname'=>array(),
            'display_name'=>array(),
            'timeout'=>array(),
            'avatar_id'=>array(),
            ),
        'history_table'=>'ciniki_user_history',
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
