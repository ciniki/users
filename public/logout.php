<?php
//
// Description
// -----------
// This method will clear all session data for a user, logging them out of the system.
//
// Info
// ----
// publish:			yes
// 
// Arguments
// ---------
// api_key:
// auth_token: 
//
// Example Return
// --------------
// <rsp stat="ok" />
//
function ciniki_users_logout($ciniki) {
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.logout', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sessionEnd');

	$rc = ciniki_core_sessionEnd($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($ciniki['request']['args']['user_selector']) && $ciniki['request']['args']['user_selector'] != '' 
        && isset($ciniki['request']['args']['user_token']) && $ciniki['request']['args']['user_token'] != '' 
        ) {
        $strsql = "DELETE FROM ciniki_user_tokens "
            . "WHERE selector = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['user_selector']) . "' "
            . "";
		$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.core');
		if( $rc['stat'] == 'ok' && $rc['num_affected_rows'] == 1 ) {
			// FIXME: Add code to track number of active sessions in users table, limit to X sessions.
		}
    }

    return array('stat'=>'ok');
}
?>
