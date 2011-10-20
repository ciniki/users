<?php
//
// Description
// -----------
// This function will return the preferred data format for a user
// if they are logged in, otherwise the default date format.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_users_datetimeFormat($ciniki) {

	//
	// Check if the user is logged in, otherwise return 
	//
	if( isset($ciniki['session']['user']['settings']['datetime_format']) && $ciniki['session']['user']['settings']['datetime_format'] != '' ) {
		return $ciniki['session']['user']['settings']['datetime_format'];
	}

	//
	// This function does not return the standard response, because it will NEVER return an error.  
	// If a problem is encountered, the it will return the default date format.
	//
	return "%b %e, %Y %l:%i %p";
}
?>
