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
// 	<bug id="1" user_id="2" subject="The bug subject" state="Open" source="ciniki-manage" source_link="mapp.menu.businesses" date_added="Nov 9, 2011 8:57 AM" last_updated="Nov 9, 2011 9:00 AM" />
// 		<followups>
//			<followup id="2" user_id="1" content="" />
// 		</followups>
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
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
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/getSettings.php');
	$rc = ciniki_bugs_getSettings($ciniki, $args['business_id'], 'ciniki.bugs.get');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/checkAccess.php');
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.get', $args['bug_id'], 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// FIXME: Add timezone information from business settings
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/timezoneOffset.php');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	// 
	// Get the bug information
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$strsql = "SELECT id, user_id, subject, state, source, source_link, "
		. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "DATE_FORMAT(CONVERT_TZ(last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
		. "FROM ciniki_bugs "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['bug_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'bugs', 'bug');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'468', 'msg'=>'Unable to load bug information', 'err'=>$rc['err']));
	}
	if( !isset($rc['bug']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'469', 'msg'=>'Unable to load bug information', 'err'=>$rc['err']));
	}
	// Setup a array to hold all the bug information
	$bug = $rc['bug'];
	
	//
	// Setup arrays to store assigned users and following
	//
	$bug['followers'] = array();
	$bug['assigned'] = array();

	//
	// Setup the array to hold all the user_ids
	//
	$user_ids = array($rc['bug']['user_id']);

    //  
    // Get the followups to the bug
    //  
    $strsql = "SELECT id, bug_id, user_id, "
        . "DATE_FORMAT(date_added, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as date_added, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(date_added) as DECIMAL(12,0)) as age, "
		. "content "
		. "FROM ciniki_bug_followups "
        . "WHERE ciniki_bug_followups.bug_id = '" . ciniki_core_dbQuote($ciniki, $args['bug_id']) . "' "
		. "ORDER BY ciniki_bug_followups.date_added ASC "
        . ""; 
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQueryPlusUserIDs.php');
	$rc = ciniki_core_dbRspQueryPlusUserIDs($ciniki, $strsql, 'bugs', 'followups', 'followup', array('stat'=>'ok', 'followups'=>array(), 'user_ids'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'467', 'msg'=>'Unable to load bug information', 'err'=>$rc['err']));
	}
	$bug['followups'] = $rc['followups'];
	$user_ids = array_merge($user_ids, $rc['user_ids']);

	//
	// Get the list of users attached to the bug
	//
	$strsql = "SELECT bug_id, user_id, perms "
		. "FROM ciniki_bug_users "
		. "WHERE bug_id = '" . ciniki_core_dbQuote($ciniki, $args['bug_id']) . "' ";
	$rc = ciniki_core_dbRspQueryPlusUserIDs($ciniki, $strsql, 'bugs', 'users', 'user', array('stat'=>'ok', 'users'=>array(), 'user_ids'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'472', 'msg'=>'Unable to load bug information', 'err'=>$rc['err']));
	}
	$bug_users = $rc['users'];
	$user_ids = array_merge($user_ids, $rc['user_ids']);

	//
	// Get the users which are linked to these accounts
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/userListByID.php');
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
			array_push($bug['assigned'], array('user'=>array('id'=>$user['user']['user_id'], 'display_name'=>$display_name)));
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
