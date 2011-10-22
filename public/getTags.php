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
// state:				The state to retrieve the bugs for (Open or Closed).
// source:				The source of bugs to get ('ciniki-manage')
// 
// name:				The very brief bug description.
// bug_text:			(optional) The longer description or additional details for the bug.
// 
// Returns
// -------
//
//
function ciniki_bugs_getTags($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
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
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.getTags', 0, 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	// business_id, module_name, table_prefix
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadGetTags.php');
	return ciniki_core_threadGetTags($ciniki, $args['business_id'], 'bugs', 'bug', 'tags', 'tag',
		array('stat'=>'ok', 'tags'=>array()), $args);
}
?>
