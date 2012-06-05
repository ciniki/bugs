<?php
//
// Description
// -----------
// This method allow's a user to close a bug.  They must have suffcient permissions to
// close the bug
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
// bug_id:				The ID of the bug to be closed.
// 
// Returns
// -------
// <rsp stat='ok'/>
//
function ciniki_bugs_close($ciniki) {
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
	// Get the module settings
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/getSettings.php');
	$rc = ciniki_bugs_getSettings($ciniki, $args['business_id'], 'ciniki.bugs.close');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/checkAccess.php');
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.close', $args['bug_id'], $ciniki['session']['user']['id']);
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Close the bug
	//
	$strsql = "UPDATE ciniki_bugs SET status = 60, last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['bug_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'bugs');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'bugs');
		return $rc;
	}

	$rc = ciniki_core_dbAddChangeLog($ciniki, 'bugs', $args['business_id'], 'ciniki_bugs', $args['bug_id'], 'status', '60');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'bugs');
		return $rc;
	}

	//
	// FIXME: Notify the other users on this thread there was an update.
	//
	// ciniki_core_threadNotifyUsers($ciniki, 'bugs', 'ciniki_bug_users', 'followup', 
	//

	//
	// Commit the changes
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
	
}
?>
