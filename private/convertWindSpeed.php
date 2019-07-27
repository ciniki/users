<?php
//
// Description
// -----------
// Convert from kph to users preferred units
//
// Returns
// -------
//
function ciniki_users_convertWindSpeed($ciniki, $speed) {

    //
    // Check if the user is logged in, otherwise return 
    //
    if( isset($ciniki['session']['user']['settings']['windspeed_units']) 
        && $ciniki['session']['user']['settings']['windspeed_units'] == 'mph' 
        ) {
        return $speed * 0.62137;
    }

    //
    // This function does not return the standard response, because it will NEVER return an error.  
    // If a problem is encountered, the it will return the default date format.
    //
    return $speed;
}
?>
