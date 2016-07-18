<?php
//
// Description
// -----------
//
// Info
// ----
// Status:          beta
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_users_timeFormat($ciniki, $format='mysql') {

    if( $format == 'php' ) {
        return "g:i A";
    }

    //
    // Check if the user is logged in, otherwise return 
    //
    if( isset($ciniki['session']['user']['settings']['time_format']) && $ciniki['session']['user']['settings']['time_format'] != '' ) {
        return $ciniki['session']['user']['settings']['time_format'];
    }

    //
    // This function does not return the standard response, because it will NEVER return an error.  
    // If a problem is encountered, the it will return the default date format.
    //
    return "%l:%i %p";
}
?>
