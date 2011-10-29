<?php
//
// Description
// -----------
// This function will return the offset for the users timezone.
// This allows the user to see all UTC dates reformatted to their time zone.
// Currently this is fixed to America/Toronto, but will be updated in the future.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_users_timezoneOffset($ciniki) {

	//
	// Check if the user is logged in, otherwise return 
	//
	$tz = timezone_open('America/Toronto');
	$utc_offset = sprintf("%+03d:00", (timezone_offset_get($tz, date_create()))/3600);

	return $utc_offset;
}
?>
