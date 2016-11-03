<?php
//
// Description
// -----------
// This method will return the logs for successful authentications.
//
// Info
// ----
// publish:         no
//
// Arguments
// ---------
// api_key:
// auth_token:
// last_timestamp:          The last timestamp from the previous request.
//
// Returns
// -------
// <rsp stat="ok>
//      <logs timestamp=''>
//          <log id='' date="2011/02/03 00:03:00" value="Value field set to" user_id="1" display_name="" />
//      </logs>
// </rsp>
//
function ciniki_users_monitorAuthLogs($ciniki) {

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.monitorAuthLogs', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteRequestArg');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    $strsql = "SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) as cur";
    $ts = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'timestamp');
    if( $ts['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.35', 'msg'=>'No timestamp available'));
    }

    //
    // Verify the field was passed, and is valid.
    //
    $last_timestamp = $ts['timestamp']['cur'] - 43200;      // Get anything that is from the last 12 hours by default.
    $last_timestamp = $ts['timestamp']['cur'] - 604800;     // Get anything that is from the last 7 days by default.
    $req_last_timestamp = $last_timestamp;
    if( isset($ciniki['request']['args']['last_timestamp']) && $ciniki['request']['args']['last_timestamp'] != '' ) {
        $req_last_timestamp = ciniki_core_dbQuoteRequestArg($ciniki, 'last_timestamp');
    }
    // Force last_timestamp to be no older than 1 week
    if( $req_last_timestamp < ($ts['timestamp']['cur'] - 604800) ) {
        $req_last_timestamp = $ts['timestamp']['cur'] - 604800;
    }

    $session = '';
    if( isset($ciniki['request']['args']['session']) ) {
        $session = ciniki_core_dbQuoteRequestArg($ciniki, 'session');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $date_format = ciniki_users_datetimeFormat($ciniki);

    // Sort the list ASC by date, so the oldest is at the bottom, and therefore will get 
    // insert at the top of the list in ciniki-manage
    $strsql = "SELECT DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as log_date"
        . ", CAST((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date)) as DECIMAL(12,0)) as age "
        . ", UNIX_TIMESTAMP(log_date) as TS"
        . ", ciniki_user_auth_log.user_id, ciniki_users.display_name, api_key, ip_address, session_key "
        . "FROM ciniki_user_auth_log, ciniki_users  "
        . "WHERE UNIX_TIMESTAMP(ciniki_user_auth_log.log_date) > '" . ciniki_core_dbQuote($ciniki, $req_last_timestamp) . "' "
        . "AND ciniki_user_auth_log.user_id = ciniki_users.id "
        . "ORDER BY TS DESC ";
    $rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'logs', 'log', array('stat'=>'ok', 'logs'=>array()));
    if( $rsp['stat'] == 'ok' ) {
        $rsp['timestamp'] = $ts['timestamp']['cur'];
    }

    return $rsp;
}
?>
