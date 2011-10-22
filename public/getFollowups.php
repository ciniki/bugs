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
// bug_text:			(optional) The longer description or additional details for the bug.
// 
// Returns
// -------
// <followups>
//		<followup id="2" user_id="1" content="" />
// </followups>
// <users>
// 		<1>
//			<user id="1" display_name="" />
//		</1>
// </users>
//
function ciniki_bugs_getFollowups($ciniki) {
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
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/getOptions.php');
	$rc = ciniki_bugs_getOptions($ciniki, $args['business_id'], 'ciniki.bugs.getFollowups');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$options = $rc['options'];

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/checkAccess.php');
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.getFollowups', $args['bug_id'], 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Setup the other arguments required for adding a thread.  These are arguments
	// which should not come through the API, but be set within the API code.
	//
	$args['options'] = 0x03;
	$args['user_id'] = $ciniki['session']['user']['id'];
	
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadGetFollowups.php');
	return ciniki_core_threadGetFollowups($ciniki, 'bugs', 'bug_followups', 'bug', $args['bug_id'], $args);
}
?>
