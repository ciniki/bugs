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
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_bugs_getSettings($ciniki, $tnid, $method) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    return ciniki_core_dbDetailsQuery($ciniki, 'ciniki_bug_settings', 'tnid', $tnid, 'ciniki.bugs', 'settings', '');
}
