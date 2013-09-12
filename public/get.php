<?php
//
// Description
// -----------
// This method will return all the information available for a bug, including followups.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 		The business the bug is attached to.
// bug_id:				The id of the bug to retrieve the information for.
// 
// Returns
// -------
// <rsp stat='ok'>
// 	<bug id="1" user_id="2" subject="The bug subject" priority="10" status="1" source="ciniki-manage" source_link="mapp.menu.businesses" date_added="Nov 9, 2011 8:57 AM" last_updated="Nov 9, 2011 9:00 AM" />
// 		<followups>
//			<followup id="2" user_id="1" content="" />
// 		</followups>
// 		<notes>
//			<followup id="2" user_id="1" content="" />
// 		</notes>
// 	</bug>
// 	<users>
// 		<1>
//			<user id="1" display_name="" />
//		</1>
// 	</users>
// </rsp>
//
function ciniki_bugs_get($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'bug_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No bug specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Get the module options
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'getSettings');
	$rc = ciniki_bugs_getSettings($ciniki, $args['business_id'], 'ciniki.bugs.get');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'checkAccess');
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.get', $args['bug_id'], 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// FIXME: Add timezone information from business settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	// 
	// Get the bug information
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$strsql = "SELECT ciniki_bugs.id, ciniki_bugs.type, "
		. "ciniki_bugs.priority, "
		. "ciniki_bugs.status, "
		. "ciniki_bugs.category, "
		. "ciniki_bugs.user_id, "
		. "ciniki_bugs.subject, "
		. "ciniki_bugs.source, "
		. "ciniki_bugs.source_link, "
		. "ciniki_bugs.options, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_bugs.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_bugs.last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
		. "FROM ciniki_bugs "
		. "LEFT JOIN ciniki_bug_users ON (ciniki_bugs.id = ciniki_bug_users.bug_id) "
		. "WHERE ciniki_bugs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_bugs.id = '" . ciniki_core_dbQuote($ciniki, $args['bug_id']) . "' "
		. "";
	// If not a sysadmin, then check they are attached to this bug, or the bug is public
	if( ($ciniki['session']['user']['perms']&0x01) == 0 ) {
		$strsql .= "AND ((ciniki_bugs.options&0x30) > 0 "
			. "OR ciniki_bug_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "OR ciniki_bugs.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. ") ";
	}
	$strsql .= "LIMIT 1 ";	// Will get multiple rows when joined to ciniki_bug_users table
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.bugs', 'bug');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'468', 'msg'=>'Unable to load bug information', 'err'=>$rc['err']));
	}
	if( !isset($rc['bug']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'469', 'msg'=>'Unable to load bug information'));
	}
	// Setup a array to hold all the bug information
	$bug = $rc['bug'];
	
	//
	// Setup arrays to store assigned users and following
	//
	$bug['followers'] = array();
	$bug['assigned'] = '';

	//
	// Setup the array to hold all the user_ids
	//
	$user_ids = array($rc['bug']['user_id']);

    //  
    // Get the followups to the bug
    //  
//    $strsql = "SELECT id, bug_id, user_id, "
 //       . "DATE_FORMAT(date_added, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as date_added, "
  //      . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(date_added) as DECIMAL(12,0)) as age, "
//		. "content "
//		. "FROM ciniki_bug_followups "
 //       . "WHERE ciniki_bug_followups.bug_id = '" . ciniki_core_dbQuote($ciniki, $args['bug_id']) . "' "
//		. "ORDER BY ciniki_bug_followups.date_added ASC "
 //       . ""; 
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQueryPlusUserIDs');
//	$rc = ciniki_core_dbRspQueryPlusUserIDs($ciniki, $strsql, 'ciniki.bugs', 'followups', 'followup', array('stat'=>'ok', 'followups'=>array(), 'user_ids'=>array()));
//	if( $rc['stat'] != 'ok' ) {
//		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'467', 'msg'=>'Unable to load bug information', 'err'=>$rc['err']));
//	}
//	$bug['followups'] = $rc['followups'];
//	$user_ids = array_merge($user_ids, $rc['user_ids']);

    //  
    // Get the followups to the bug
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadGetFollowups');
	$rc = ciniki_core_threadGetFollowups($ciniki, 'ciniki.bugs', 'ciniki_bug_followups', 
		'bug', $args['bug_id'], array());
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'467', 'msg'=>'Unable to load bug followups', 'err'=>$rc['err']));
	}
	if( isset($rc['followups']) ) {
		$bug['followups'] = $rc['followups'];
	}

    //  
    // Get the notes to the bug
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadGetFollowups');
	$rc = ciniki_core_threadGetFollowups($ciniki, 'ciniki.bugs', 'ciniki_bug_notes', 
		'bug', $args['bug_id'], array());
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1335', 'msg'=>'Unable to load bug notes', 'err'=>$rc['err']));
	}
	if( isset($rc['followups']) ) {
		$bug['notes'] = $rc['followups'];
	}

	//
	// Get the list of users attached to the bug
	//
	$strsql = "SELECT bug_id, user_id, perms "
		. "FROM ciniki_bug_users "
		. "WHERE bug_id = '" . ciniki_core_dbQuote($ciniki, $args['bug_id']) . "' ";
	$rc = ciniki_core_dbRspQueryPlusUserIDs($ciniki, $strsql, 'ciniki.bugs', 'users', 'user', array('stat'=>'ok', 'users'=>array(), 'user_ids'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'472', 'msg'=>'Unable to load bug information', 'err'=>$rc['err']));
	}
	$bug_users = $rc['users'];
	$user_ids = array_merge($user_ids, $rc['user_ids']);

	//
	// Get the users which are linked to these accounts
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'userListByID');
	$rc = ciniki_users_userListByID($ciniki, 'users', $user_ids, 'display_name');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'470', 'msg'=>'Unable to load bug information', 'err'=>$rc['err']));
	}
	if( !isset($rc['users']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'471', 'msg'=>'Unable to load bug information', 'err'=>$rc['err']));
	}
	$users = $rc['users'];

	//
	// Build the list of followers and users assigned to the bug
	//
	foreach($bug_users as $unum => $user) {
		$display_name = 'unknown';
		if( isset($users[$user['user']['user_id']]) ) {
			$display_name = $users[$user['user']['user_id']]['display_name'];
		}
		// Followers
		if( ($user['user']['perms'] & 0x01) > 0 ) {
			array_push($bug['followers'], array('user'=>array('id'=>$user['user']['user_id'], 'display_name'=>$display_name)));
		}
		// Assigned to
		if( ($user['user']['perms'] & 0x02) > 0 ) {
			if( $bug['assigned'] != '' ) {
				$bug['assigned'] .= ',';
			}
			$bug['assigned'] .= $user['user']['user_id'];
		}
	}

	//
	// Fill in the followup information with user info
	//
	foreach($bug['followups'] as $fnum => $followup) {
		$display_name = 'unknown';
		if( isset($users[$followup['followup']['user_id']]) ) {
			$display_name = $users[$followup['followup']['user_id']]['display_name'];
		}
		$bug['followups'][$fnum]['followup']['user_display_name'] = $display_name;
	}

	//
	// Fill in the bug information with user info
	//
	if( isset($bug['user_id']) && isset($users[$bug['user_id']]) ) {
		$bug['user_display_name'] = $users[$bug['user_id']]['display_name'];
	}

	//
	// Return the bug information
	//
	return array('stat'=>'ok', 'bug'=>$bug);
}
?>
