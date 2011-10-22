<?php
//
// Description
// -----------
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
// bug_text:			(optional) The longer description or additional details for the bug.
// 
// Returns
// -------
// <rsp stat='ok' id='1' />
//
function ciniki_bugs_add($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'state'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'Must specify Open or Closed',
			'accepted'=>array('Open', 'Closed')), 
		'subject'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No subject specified'), 
		'source'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>''), 
		'source_link'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>''), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Get the module options
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/getOptions.php');
	$rc = ciniki_bugs_getOptions($ciniki, $args['business_id'], 'ciniki.bugs.add');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$options = $rc['options'];

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/checkAccess.php');
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.add', 0, 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Setup the other arguments required for adding a thread.  These are arguments
	// which should not come through the API, but be set within the API code.
	//
	$args['options'] = 0x03;
	$args['user_id'] = $ciniki['session']['user']['id'];

	//
	// Turn off autocommit
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Add the bug to the database using the thread libraries
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAdd.php');
	$rc = ciniki_core_threadAdd($ciniki, 'bugs', 'bugs', $args);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'bugs');
		return $rc;
	}
	$bug_id = $rc['insert_id'];
	if( $bug_id < 1 ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'215', 'msg'=>'Internal Error', 'pmsg'=>'Unable to add bug.'));
	}

	//
	// Add a followup if they included details
	//
	if( isset($ciniki['request']['args']['followup']) && $ciniki['request']['args']['followup'] != '' ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddFollowup.php');
		$rc = ciniki_core_threadAddFollowup($ciniki, 'bugs', 'bug_followups', 'bug', $bug_id, array(
			'user_id'=>$ciniki['session']['user']['id'],
			'bug_id'=>$bug_id,
			'content'=>$ciniki['request']['args']['followup']
			));
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'bugs');
			return $rc;
		}
	}
	
	//
	// Attach the user to the bug_users as a follower
	// $ciniki, $module, $prefix, {$prefix}_id, options
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddFollower.php');
	$rc = ciniki_core_threadAddFollower($ciniki, 'bugs', 'bug_users', 'bug', $bug_id, $ciniki['session']['user']['id']);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'bugs');
		return $rc;
	}

	//
	// FIXME: Check the options to see if there's anybody who should be auto attached and emailed
	//
	if( isset($options['bugs.options.notify_owners']) && $options['bugs.options.notify_owners'] == 'yes' ) {
		//
		//	FIXME: Email the owners a bug was added to the system.
		//
		//  Not sure if this is needed, could just add users from group bug tracking
		//
	}

	//
	// FIXME: Attach business users who are bug trackers
	//
	if( isset($options['bugs.options.attach_group_users']) && $options['bugs.options.attach_group_users'] == 'yes' ) {
		//
		// Select the users attached to the business and bug tracking module
		//
		$strsql = "SELECT user_id FROM business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (groups & 0x400) = 0x0400 ";
		
		// threadAddFollower($ciniki, 'bugs', 'bug', $bug_id, $user_id, array());
	}

	//
	// FIXME: Add tags
	//
	if( isset($ciniki['request']['args']['tags']) && $ciniki['request']['args']['tags'] != '' ) {
		// require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddTags.php');
		// threadAddTags($ciniki, 'bugs', 'bug', $bug_id);
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$bug_id);
	
}
?>
