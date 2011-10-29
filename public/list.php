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
	
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadGetList.php');
	return ciniki_core_threadGetList($ciniki, 'bugs', 'bugs', 'bugs', 'bug', $args);
}
?>
