<?php
//
// Description
// -----------
// This method will get detail values for a user.  These values
// are used in the UI.
//
// Info
// ----
// publish:			yes
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:				The ID of the user to get the details for.
// keys:				The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//		<user firstname='' lastname='' display_name=''/>
//  	<settings date_format='' />
// </details>
//
function ciniki_users_getDetails($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'keys'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.getDetails', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');

	// Split the keys, if specified
	$detail_keys = preg_split('/,/', $args['keys']);

	$rsp = array('stat'=>'ok', 'details'=>array());

	foreach($detail_keys as $detail_key) {
		if( $detail_key == 'user' ) {
			$strsql = "SELECT firstname, lastname, display_name FROM ciniki_users "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.details', 'user');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$rsp['details']['user.firstname'] = $rc['user']['firstname'];
			$rsp['details']['user.lastname'] = $rc['user']['lastname'];
			$rsp['details']['user.display_name'] = $rc['user']['display_name'];
		} elseif( in_array($detail_key, array('ui','settings')) ) {
			$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_user_details', 'user_id', $args['user_id'], 'ciniki.users', 'details', $detail_key);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}

			if( $rc['details'] != null ) {
				$rsp['details'] += $rc['details'];
			}
		}
	}

	return $rsp;
}
?>
