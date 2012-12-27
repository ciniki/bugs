<?php
//
// Description
// -----------
// This function will add a followup to a bug
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
// bug_id:				The bug to attach the follow up.
// content:				The content of the reply to attach.
// 
// Returns
// -------
// <rsp stat='ok' id='1' />
//
function ciniki_bugs_addFollowup($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'bug_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No bug specified'),
		'content'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No content'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Get the module settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'getSettings');
	$rc = ciniki_bugs_getSettings($ciniki, $args['business_id'], 'ciniki.bugs.addFollowup');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'checkAccess');
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.addFollowup', $args['bug_id'], 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Setup the other arguments required for adding a thread.  These are arguments
	// which should not come through the API, but be set within the API code.
	//
	$args['user_id'] = $ciniki['session']['user']['id'];

	// 
	// Turn of auto commit in the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Add a followup 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollowup');
	$rc = ciniki_core_threadAddFollowup($ciniki, 'ciniki.bugs', 'ciniki_bug_followups', 'bug', $args['bug_id'], $args);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.bugs');
		return $rc;
	}

	//
	// Make sure the user is attached as a follower.  They may already be attached, but it
	// will make sure the flag is set.
	// 
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollower');
	$rc = ciniki_core_threadAddFollower($ciniki, 'ciniki.bugs', 'ciniki_bug_users', 'bug', $args['bug_id'], $ciniki['session']['user']['id']);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.bugs');
		return $rc;
	}

	//
	// Update the last updated of the main thread
	//
	$strsql = "UPDATE ciniki_bugs SET last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['bug_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.bugs');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.bugs');
		return $rc;
	}

	//
	// Commit the changes
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the subject
	//
	$strsql = "SELECT subject "
		. "FROM ciniki_bugs "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['bug_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.bugs', 'bug');
	if( $rc['stat'] != 'ok' || !isset($rc['bug']) || !is_array($rc['bug']) ) {
		return $rc;
	}
	$bug = $rc['bug'];

	//
	// Notify the other users on this thread there was an update.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadNotifyUsers');
	$rc = ciniki_core_threadNotifyUsers($ciniki, 'ciniki.bugs', 'ciniki_bug_users', 'bug', $args['bug_id'], 0x01, 
		$ciniki['session']['user']['display_name'] . " replied to bug #" . $args['bug_id'] . ': ' . $bug['subject'], 
			$args['content'] 
			. "\n\n"
		);

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'bugs');


	return array('stat'=>'ok');
}
?>
