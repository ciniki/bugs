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
//		<bug id="1" user_id="1" subject="The bug subject" state="Open" source="ciniki-manage" source_link="mapp.menu.businesses" age="2 days" updated_age="1 day" />
// </bugs>
// <users>
// 		<1>
//			<user id="1" display_name="" />
//		</1>
// </users>
//
function ciniki_bugs_list($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'type'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'errmsg'=>''), 
		'category'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No category specified'), 
		'state'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'Must specify Open or Closed',
			'accepted'=>array('Open', 'Closed')), 
		'subject'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No subject specified'), 
		'source'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>''), 
		'source_link'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>''), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	if( $args['category'] == 'Uncategorized' ) {
		$args['category'] = '';
	}

	//
	// Get the module settings
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/getSettings.php');
	$rc = ciniki_bugs_getSettings($ciniki, $args['business_id'], 'ciniki.bugs.list');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/checkAccess.php');
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.list', 0, 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Setup the other arguments required for adding a thread.  These are arguments
	// which should not come through the API, but be set within the API code.
	//
	$args['options'] = 0x03;
	// $args['user_id'] = $ciniki['session']['user']['id'];
	
//	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadGetList.php');
//	return ciniki_core_threadGetList($ciniki, 'bugs', 'ciniki_bugs', 'bugs', 'bug', $args);
	//
	// FIXME: Add timezone information from business settings
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/timezoneOffset.php');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "SELECT id, business_id, user_id, subject, state, "
		. "source, source_link, "
		. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "DATE_FORMAT(CONVERT_TZ(last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
		. "FROM ciniki_bugs "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ";
	if( isset($args['type']) && $args['type'] != '' ) {
		$strsql .= "AND type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
	}
	if( isset($args['category']) ) {
		$strsql .= "AND category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
	}

	// state - optional
	if( isset($args['state']) ) {
		$strsql .= "AND state = '" . ciniki_core_dbQuote($ciniki, $args['state']) . "' ";
	} else {
		//
		// Default to a blank state, which should be none for any threads using states,
		// or will be blank if the thread does not use a state.   Either way, it's the
		// best default option.
		//
		$strsql .= "AND state = '' ";
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

	$strsql .= "ORDER BY id ";

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'bugs', 'bugs', 'bug', array('stat'=>'ok', 'bugs'=>array()));
}
?>
