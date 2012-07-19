<?php
//
// Description
// -----------
// Search bugs by subject and date
//
// Returns
// -------
//
function ciniki_bugs_searchQuick($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No search specified'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No limit specified'), 
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
    $rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.searchQuick', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the number of messages in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT ciniki_bugs.id, type, priority, subject, source, source_link, "
//		. "IF((ciniki_atdos.flags&0x02)=2, 'yes', 'no') AS private, "
		. "IF((u1.perms&0x02)=2, 'yes', 'no') AS assigned, "
//		. "IF((u1.perms&0x08)=8, 'yes', 'no') AS viewed, "
		. "IFNULL(u3.display_name, '') AS assigned_users "
		. "FROM ciniki_bugs "
		. "LEFT JOIN ciniki_bug_users AS u1 ON (ciniki_bugs.id = u1.bug_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
		. "LEFT JOIN ciniki_bug_users AS u2 ON (ciniki_bugs.id = u2.bug_id && (u2.perms&0x02) = 2) "
		. "LEFT JOIN ciniki_users AS u3 ON (u2.user_id = u3.id) "
		. "LEFT JOIN ciniki_bug_followups ON (ciniki_bugs.id = ciniki_bug_followups.bug_id) "
		. "WHERE ciniki_bugs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_bugs.status = 1 "		// Open bugs/features
		. "AND (subject LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR subject LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_bug_followups.content LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_bug_followups.content LIKE ' %" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' ";
	if( is_integer($args['start_needle']) ) {
		$strsql .= "OR ciniki_bugs.id LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' ";
	}

	$strsql .= ") "
		. "";
	// Check for public/private bugs, and if private make sure user created or is assigned
//	$strsql .= "AND ((perm_flags&0x01) = 0 "  // Public to business
//			// created by the user requesting the list
//			. "OR ((perm_flags&0x01) = 1 AND ciniki_bugs.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
//			// Assigned to the user requesting the list, and the user hasn't deleted the message
//			. "OR ((perm_flags&0x01) = 1 AND (u1.perms&0x02) = 0x02 AND (u1.perms&0x10) <> 0x10 ) "
//			. ") "
	$strsql .= "GROUP BY ciniki_bugs.id, u3.id "
		. "ORDER BY assigned DESC, ciniki_bugs.id, u3.display_name "
		. "";
	if( isset($args['limit']) && $args['limit'] != '' && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} else {
		$strsql .= "LIMIT 25 ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'bugs', array(
		array('container'=>'bugs', 'fname'=>'id', 'name'=>'bug',
			'fields'=>array('id', 'type', 'subject', 'priority', 'status', 'assigned', 'assigned_users', 'source', 'source_link'), 
			'lists'=>array('assigned_users'),
			'maps'=>array('type'=>array('1'=>'Bug', '2'=>'Feature'))),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['bugs']) ) {
		return array('stat'=>'ok', 'bugs'=>array());
	}
	return array('stat'=>'ok', 'bugs'=>$rc['bugs']);
}
?>
