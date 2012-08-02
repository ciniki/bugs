<?php
//
// Description
// ===========
// This method will return the stats for bugs/features by category
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_bugs_stats($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/checkAccess.php');
    $rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.stats', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');

	$rsp = array('stat'=>'ok', 
//		'bugs'=>array('priorities'=>array(
//			'High'=>array('name'=>'High', 'count'=>'0'),
//			'Medium'=>array('name'=>'Medium', 'count'=>'0'),
//			'Low'=>array('name'=>'Low', 'count'=>'0'),
//			)), 
//		'features'=>array('priorities'=>array(
//			'High'=>array('name'=>'High', 'count'=>'0'),
//			'Medium'=>array('name'=>'Medium', 'count'=>'0'),
//			'Low'=>array('name'=>'Low', 'count'=>'0'),
//			)),
		);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
	
	//
	// Get type stats
	//
	$strsql = "SELECT type AS type_name, COUNT(*) AS count, "
		. "IF(category='', 'Uncategorized', category) AS name "
		. "FROM ciniki_bugs "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND status = 1 "
		. "GROUP BY ciniki_bugs.type, ciniki_bugs.category "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.bugs', array(
		array('container'=>'types', 'fname'=>'type_name', 'name'=>'type',
			'fields'=>array('type_name'), 
			'maps'=>array('type_name'=>array(''=>'unknown', '1'=>'bugs', '2'=>'features')),
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
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND status = 1 "
		. "GROUP BY ciniki_bugs.type, ciniki_bugs.priority DESC "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.bugs', array(
		array('container'=>'types', 'fname'=>'type_name', 'name'=>'type',
			'fields'=>array('type_name'), 
			'maps'=>array('type_name'=>array(''=>'unknown', '1'=>'bugs', '2'=>'features')),
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

	return $rsp;
}
?>
