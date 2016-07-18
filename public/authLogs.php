<?php
//
// Description
// -----------
// This method will return the logs for user.
//
// Info
// ----
// publish:         no
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:             The ID of the user to get the logs for.
// limit:               (optional) The maximum number of logs to return, default is 25.
//
// Returns
// -------
// <rsp stat="ok>
//      <logs timestamp=''>
//          <log id='' date="2011/02/03 00:03:00" value="Value field set to" user_id="1" display_name="" />
//      </logs>
// </rsp>
//
function ciniki_users_authLogs($ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'),
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'),
        ));
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.authLogs', $args['user_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $date_format = ciniki_users_datetimeFormat($ciniki);

    // Sort the list ASC by date, so the oldest is at the bottom, and therefore will get 
    // insert at the top of the list in ciniki-manage
    $strsql = "SELECT DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as log_date"
        . ", CAST((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date)) as DECIMAL(12,0)) as age "
        . ", UNIX_TIMESTAMP(log_date) as TS"
        . ", ciniki_user_auth_log.user_id, ciniki_users.display_name, api_key, ip_address, session_key "
        . "FROM ciniki_user_auth_log, ciniki_users  "
        . "WHERE ciniki_user_auth_log.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "AND ciniki_user_auth_log.user_id = ciniki_users.id "
        . "ORDER BY TS DESC ";
    
    if( isset($args['limit']) && $args['limit'] != '' && is_numeric($args['limit'])) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }

    $rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'logs', 'log', array('stat'=>'ok', 'logs'=>array()));
    return $rsp;
}
?>
