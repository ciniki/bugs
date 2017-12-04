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
// tnid:         The tenant the bug is attached to.
// name:                The very brief bug description.
// bug_text:            (optional) The longer description or additional details for the bug.
// 
// Returns
// -------
// <followups>
//      <followup id="2" user_id="1" content="" />
// </followups>
// <users>
//      <1>
//          <user id="1" display_name="" />
//      </1>
// </users>
//
function ciniki_bugs_bugGetFollowups($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'bug_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Bug'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Get the module options
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'getSettings');
    $rc = ciniki_bugs_getSettings($ciniki, $args['tnid'], 'ciniki.bugs.bugGetFollowups');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'checkAccess');
    $rc = ciniki_bugs_checkAccess($ciniki, $args['tnid'], 'ciniki.bugs.bugGetFollowups', $args['bug_id'], 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Setup the other arguments required for adding a thread.  These are arguments
    // which should not come through the API, but be set within the API code.
    //
    $args['options'] = 0x03;
    $args['user_id'] = $ciniki['session']['user']['id'];
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadGetFollowups');
    return ciniki_core_threadGetFollowups($ciniki, 'ciniki.bugs', 'ciniki_bug_followups', 'bug', $args['bug_id'], $args);
}
?>
