<?php
//
// Description
// -----------
// Convert from celsius to user preferred units
//
// Returns
// -------
//
function ciniki_users_convertTemperature($ciniki, $temp) {

    //
    // Check if the user is logged in, otherwise return 
    //
    if( isset($ciniki['session']['user']['settings']['temperature_units']) 
        && $ciniki['session']['user']['settings']['temperature_units'] == 'fahrenheit' 
        ) {
        if( $temp == 0 ) {
            return 32;
        } else {
            return round((($temp * 9) / 5) + 32, 2);
        }
    }

    //
    // This function does not return the standard response, because it will NEVER return an error.  
    // If a problem is encountered, the it will return the default date format.
    //
    return $temp;
}
?>
