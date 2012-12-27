<?php
//
// Description
// -----------
// This function will return the list of settings for the bugs module.
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
function ciniki_bugs_getSettings($ciniki, $business_id, $method) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
	return ciniki_core_dbDetailsQuery($ciniki, 'ciniki_bug_settings', 'business_id', $business_id, 'ciniki.bugs', 'settings', '');
}
