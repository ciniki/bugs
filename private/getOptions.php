<?php
//
// Description
// -----------
// This function will return the list of options for the module as found 
// in the business_details table.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_bugs_getOptions($ciniki, $business_id, $method) {
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQuery.php');
	return ciniki_core_dbDetailsQuery($ciniki, 'business_details', 'business_id', $business_id, 'businesses', 'options', 'bugs.options');
}
