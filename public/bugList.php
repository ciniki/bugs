<?php
//
// Description
// -----------
// This method will retrieve a list of bugs from the database.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 		The business the bug is attached to.
// name:				The very brief bug description.
// 
// Returns
// -------
// <bugs>
//		<bug id="1" user_id="1" subject="The bug subject" source="ciniki-manage" source_link="mapp.menu.businesses" age="2 days" updated_age="1 day" />
// </bugs>
// <users>
// 		<1>
//			<user id="1" display_name="" />
//		</1>
// </users>
//
function ciniki_bugs_bugList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'), 
		'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
		'priority'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Priority'), 
		'status'=>array('required'=>'Yes', 'blank'=>'no', 'name'=>'Status'),
		'subject'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subject'), 
		'source'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Source'), 
		'source_link'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Source Link'), 
		'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'), 
		'order'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	if( isset($args['category']) && $args['category'] == 'Uncategorized' ) {
		$args['category'] = '';
	}

	//
	// Get the module settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'getSettings');
	$rc = ciniki_bugs_getSettings($ciniki, $args['business_id'], 'ciniki.bugs.bugList');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'checkAccess');
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.bugList', 0, 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Setup the other arguments required for adding a thread.  These are arguments
	// which should not come through the API, but be set within the API code.
	//
	$args['options'] = 0x03;
	// $args['user_id'] = $ciniki['session']['user']['id'];
	
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadGetList');
//	return ciniki_core_threadGetList($ciniki, 'ciniki.bugs', 'ciniki_bugs', 'bugs', 'bug', $args);
	//
	// FIXME: Add timezone information from business settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "SELECT ciniki_bugs.id, "
		. "ciniki_bugs.business_id, "
		. "ciniki_bugs.user_id, "
		. "ciniki_bugs.type, "
		. "ciniki_bugs.priority, "
//		. "CASE ciniki_bugs.type WHEN 1 THEN 'bugs' WHEN 2 THEN 'features' WHEN 3 THEN 'questions' END AS typename, "
		. "ciniki_bugs.status, "
		. "ciniki_bugs.subject, "
		. "ciniki_bugs.source, "
		. "ciniki_bugs.source_link, "
		. "ciniki_bugs.status AS status_text, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_bugs.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_bugs.last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
		. ", IFNULL(u3.display_name, '') AS assigned_users "
		. "FROM ciniki_bugs "
		. "LEFT JOIN ciniki_bug_users AS u2 ON (ciniki_bugs.id = u2.bug_id && (u2.perms&0x02) = 2) "
		. "LEFT JOIN ciniki_users AS u3 ON (u2.user_id = u3.id) "
		. "WHERE ciniki_bugs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ";
	if( isset($args['type']) && $args['type'] != '' && $args['type'] != 0 && $args['type'] != 'all' ) {
		$strsql .= "AND type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
	}
	if( isset($args['category']) ) {
		$strsql .= "AND category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
	}
	if( isset($args['priority']) ) {
		$strsql .= "AND priority = '" . ciniki_core_dbQuote($ciniki, $args['priority']) . "' ";
	}

	// status - optional
	if( isset($args['status']) && $args['status'] != 'all' ) {
		$strsql .= "AND ciniki_bugs.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
	} elseif( !isset($args['status']) || $args['status'] != 'all' ) {
		//
		// Default to open status
		//
		$strsql .= "AND ciniki_bugs.status = 1 ";
	}

	// user_id
	if( isset($args['user_id']) && $args['user_id'] > 0 ) {
		$strsql .= "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
	}

	// subject
	if( isset($args['subject']) ) {
		$strsql .= "AND subject = '" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', ";
	}

	// source - optional
	if( isset($args['source']) ) {
		$strsql .= "AND source = '" . ciniki_core_dbQuote($ciniki, $args['source']) . "' ";
	}

	// source_link - optional
	if( isset($args['source_link']) ) {
		$strsql .= "AND source_link = '" . ciniki_core_dbQuote($ciniki, $args['source_link']) . "' ";
	}

	// If not a sysadmin, then check they are attached to this bug, or the bug is public
	if( ($ciniki['session']['user']['perms']&0x01) == 0 ) {
		$strsql .= "AND ((ciniki_bugs.options&0x30) > 0 "
			. "OR u2.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "OR ciniki_bugs.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. ") ";
	}

	if( isset($args['order']) && $args['order'] == 'openclosed' ) {
		$strsql .= "ORDER BY status, last_updated, id ";
	} elseif( isset($args['order']) && $args['order'] == 'latestupdated' ) {	
		$strsql .= "ORDER BY last_updated, id ";
	} elseif( isset($args['order']) && $args['order'] == 'type' ) {
		$strsql .= "ORDER BY type, last_updated, id ";
	} else {
		$strsql .= "ORDER BY id ";
	}

	// Check for a requested limit
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

	//
	// Return two lists for open and closed
	//
	if( isset($args['order']) && $args['order'] == 'openclosed' ) {
		error_log($strsql);
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.bugs', array(
			array('container'=>'status', 'fname'=>'status', 'name'=>'status',
				'fields'=>array('status', 'name'=>'status_text')),
			array('container'=>'bugs', 'fname'=>'id', 'name'=>'bug',
				'fields'=>array('id', 'business_id', 'user_id', 'type', 'priority', 
					'status', 'status_text', 'subject', 
					'source', 'source_link', 'date_added', 'last_updated', 'assigned_users'),
				'lists'=>array('assigned_users'),
				'maps'=>array('status_text'=>array('0'=>'Unknown', '1'=>'Open', '60'=>'Closed'),
					'type'=>array('1'=>'Bug', '2'=>'Feature', '3'=>'Question')) ),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$rsp = array('stat'=>'ok', 'open'=>array(), 'closed'=>array());
		foreach($rc['status'] as $s => $status) {
			if( $status['status']['status'] == '1' ) {
				$rsp['open'] = $status['status']['bugs'];
			} elseif( $status['status']['status'] == '60' ) {
				$rsp['closed'] = $status['status']['bugs'];
			}
		}
		return $rsp;
	} 

	//
	// Return bugs sorted by type
	//
	elseif( isset($args['order']) && $args['order'] == 'type' ) {
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.bugs', array(
			array('container'=>'types', 'fname'=>'type', 'name'=>'type',
				'fields'=>array('id'=>'type')),
			array('container'=>'bugs', 'fname'=>'id', 'name'=>'bug',
				'fields'=>array('id', 'business_id', 'user_id', 'type', 'priority', 
					'status', 'status_text', 'subject', 
					'source', 'source_link', 'date_added', 'last_updated', 'assigned_users'),
				'lists'=>array('assigned_users'),
				'maps'=>array('status_text'=>array('0'=>'Unknown', '1'=>'Open', '60'=>'Closed'),
					'type'=>array('1'=>'Bug', '2'=>'Feature', '3'=>'Question')) ),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$rsp = array('stat'=>'ok');
		if( isset($rc['types']) ) {
			foreach($rc['types'] as $type) {
				if( $type['type']['id'] == 1 ) {
					$rsp['bugs'] = $type['type']['bugs'];
				} elseif( $type['type']['id'] == 2 ) {
					$rsp['features'] = $type['type']['bugs'];
				} elseif( $type['type']['id'] == 3 ) {
					$rsp['questions'] = $type['type']['bugs'];
				}
			}
		}
		return $rsp;
	} 

	//
	// Return a single list of bugs
	//
	else {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.bugs', array(
			array('container'=>'bugs', 'fname'=>'id', 'name'=>'bug',
				'fields'=>array('id', 'business_id', 'user_id', 'type', 'priority', 'status', 'status_text', 'subject', 
					'source', 'source_link', 'date_added', 'last_updated', 'assigned_users'),
				'lists'=>array('assigned_users'),
				'maps'=>array('status_text'=>array('0'=>'Unknown', '1'=>'Open', '60'=>'Closed'),
					'type'=>array('1'=>'Bug', '2'=>'Feature', '3'=>'Question')) ),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['bugs']) ) {
			return array('stat'=>'ok', 'bugs'=>array());
		}
	}

	return $rc;
}
?>
