<?php
//
// Description
// ===========
// This method will return the stats for bugs/features by category
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_bugs_bugStats($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'checkAccess');
    $rc = ciniki_bugs_checkAccess($ciniki, $args['tnid'], 'ciniki.bugs.bugStats', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    $rsp = array('stat'=>'ok', 
//      'bugs'=>array('priorities'=>array(
//          'High'=>array('name'=>'High', 'count'=>'0'),
//          'Medium'=>array('name'=>'Medium', 'count'=>'0'),
//          'Low'=>array('name'=>'Low', 'count'=>'0'),
//          )), 
//      'features'=>array('priorities'=>array(
//          'High'=>array('name'=>'High', 'count'=>'0'),
//          'Medium'=>array('name'=>'Medium', 'count'=>'0'),
//          'Low'=>array('name'=>'Low', 'count'=>'0'),
//          )),
        );
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    
    //
    // Get type stats
    //
    $strsql = "SELECT type AS type_name, COUNT(*) AS count, "
        . "IF(category='', 'Uncategorized', category) AS name "
        . "FROM ciniki_bugs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status = 1 "
        . "GROUP BY ciniki_bugs.type, ciniki_bugs.category "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.bugs', array(
        array('container'=>'types', 'fname'=>'type_name', 'name'=>'type',
            'fields'=>array('type_name'), 
            'maps'=>array('type_name'=>array(''=>'unknown', '1'=>'bugs', '2'=>'features', '3'=>'questions')),
            ),
        array('container'=>'categories', 'fname'=>'name', 'name'=>'category',
            'fields'=>array('name', 'count')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($rc['types']) ) {
        foreach($rc['types'] as $tnum => $type) {
            $rsp[$type['type']['type_name']]['categories'] = $type['type']['categories'];
        }
    }

    //
    // Get priority stats
    //
    $strsql = "SELECT type AS type_name, priority AS name, COUNT(*) AS count "
        . "FROM ciniki_bugs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status = 1 "
        . "GROUP BY ciniki_bugs.type, ciniki_bugs.priority "
        . "ORDER BY ciniki_bugs.type, ciniki_bugs.priority "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.bugs', array(
        array('container'=>'types', 'fname'=>'type_name', 'name'=>'type',
            'fields'=>array('type_name'), 
            'maps'=>array('type_name'=>array(''=>'unknown', '1'=>'bugs', '2'=>'features', '3'=>'questions')),
            ),
        array('container'=>'priorities', 'fname'=>'name', 'name'=>'priority',
            'fields'=>array('name', 'count'),
            'maps'=>array('name'=>array('0'=>'Unknown', '10'=>'Low', '30'=>'Medium', '50'=>'High')),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($rc['types']) ) {
        foreach($rc['types'] as $tnum => $type) {
            $rsp[$type['type']['type_name']]['priorities'] = $type['type']['priorities'];
        }
    }

    //
    // Get the latest submissions from the last 72 hours that are open
    //
    $strsql = "SELECT ciniki_bugs.id, "
        . "ciniki_bugs.tnid, "
        . "ciniki_bugs.user_id, "
        . "ciniki_bugs.type, "
        . "ciniki_bugs.priority, "
        . "ciniki_bugs.status, "
        . "ciniki_bugs.subject, "
        . "ciniki_bugs.source, "
        . "ciniki_bugs.source_link, "
        . "ciniki_bugs.status AS status_text, "
        . "DATE_FORMAT(CONVERT_TZ(ciniki_bugs.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
        . "DATE_FORMAT(CONVERT_TZ(ciniki_bugs.last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
        . "FROM ciniki_bugs "
        . "WHERE ciniki_bugs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_bugs.status = 1 "
        . "AND UNIX_TIMESTAMP(ciniki_bugs.date_added) > (UNIX_TIMESTAMP() - 259200) "
        . "ORDER BY date_added DESC "
        . "LIMIT 15"
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.bugs', array(
        array('container'=>'bugs', 'fname'=>'id', 'name'=>'bug',
            'fields'=>array('id', 'tnid', 'user_id', 'type', 'priority', 
                'status', 'status_text', 'subject', 
                'source', 'source_link', 'date_added', 'last_updated'),
            'maps'=>array('status_text'=>array('0'=>'Unknown', '1'=>'Open', '60'=>'Closed'),
                'type'=>array('1'=>'Bug', '2'=>'Feature', '3'=>'Question')) ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['bugs']) ) {
        $rsp['latest'] = $rc['bugs'];
    } else {
        $rsp['latest'] = array();
    }

    return $rsp;
}
?>
