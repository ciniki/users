<?php
//
// Description
// -----------
// This function will format the repeat description for a
// calendar or other repeatable event.
//
// Arguments
// ---------
// ciniki:
// repeat_type:		The type of repeat to format
//	
//					10 - daily
//					20 - weekly
//					30 - monthly by date
//					31 - monthly by weekday
//					40 - yearly
//
// repeat_interval:	The interval between repeats, 
//					number of days, weeks, months or years.
// 
// Returns
// -------
//
function ciniki_users_formatRepeatDescription($ciniki, $type, $interval, $dayofmonth, $day, $weekday) {

	$desc = '';
	$nth = array('1st', '2nd', '3rd', '4th', '5th');
	// Daily
	if( $type == 10 && $interval == 1 ) {
		$desc = 'every day';
	} elseif( $type == 10 && $interval > 1 ) {
		$desc = 'every ' . $interval . " days";

	// Weekly
	} elseif( $type == 20 && $interval = 1 ) {
		$desc = 'every week';
	} elseif( $type == 20 && $interval > 1 ) {
		$desc = 'every ' . $interval . " weeks";

	// Monthly
	} elseif( $type == 30 ) {
		if( $interval == 1 ) {
			$desc = 'every month';
		} elseif( $interval > 1 ) {
			$desc = 'every ' . $interval . ' months';
		}
		if( $dayofmonth != '' ) {
			$desc .= ' on the ' . $dayofmonth;
		}
		
	} elseif( $type == 31 ) {
		if( $interval == 1 ) {
			$desc = 'every month';
		} elseif( $interval > 1 ) {
			$desc = 'every ' . $interval . ' months';
		}
		if( $day != '' && $weekday != '' ) {
			$desc .= ' on the ' . $nth[floor($day/7)] . ' ' . $weekday;
		}

	// Yearly
	} elseif( $type == 40 && $interval == 1 ) {
		$desc = 'every year';
	} elseif( $type == 40 && $interval > 1 ) {
		$desc = 'every ' . $interval . " years";
	}

	return array('stat'=>'ok', 'description'=>$desc);
}
?>
