<?php
//
// Description
// -----------
// This method adds a new bug report to the bugs module.
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
// <rsp stat='ok' id='1' />
//
function ciniki_bugs_bugAdd(&$ciniki) {
    //
    // Track if the submitter should be emailed, if submitter is owner, we don't want to email twice
    // 
    $email_submitter = 'yes';
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'type'=>array('required'=>'no', 'blank'=>'no', 'default'=>'1', 'name'=>'Type'), 
        'subject'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subject'), 
        'priority'=>array('required'=>'no', 'default'=>'10', 'blank'=>'yes', 'name'=>'Priority'), 
        'status'=>array('required'=>'no', 'default'=>'1', 'blank'=>'no', 'name'=>'Status'), 
        'category'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Category'), 
        'options'=>array('required'=>'no', 'blank'=>'no', 'default'=>0x03, 'name'=>'Flags'), 
        'source'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Source'), 
        'source_link'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Source Link'), 
        'followup'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Content'), 
        'notesfollowup'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
        'assigned'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Assigned'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    if( $args['priority'] == '' || $args['priority'] == '0' ) {
        $args['priority'] = 10;
    }

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'checkAccess');
    $rc = ciniki_bugs_checkAccess($ciniki, $args['tnid'], 'ciniki.bugs.bugAdd', 0, 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Get the module settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'getSettings');
    $rc = ciniki_bugs_getSettings($ciniki, $args['tnid'], 'ciniki.bugs.bugAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // Setup the other arguments required for adding a thread.  These are arguments
    // which should not come through the API, but be set within the API code.
    //

//  $args['options'] = 0x03;
    $args['user_id'] = $ciniki['session']['user']['id'];

    //
    // Turn off autocommit
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.bugs');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.bugs.bug', $args, 0x02);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.bugs');
        return $rc;
    }
    $bug_id = $rc['id'];

    //
    // Add a followup if they included details
    //
    if( isset($args['followup']) && $args['followup'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollowup');
        $rc = ciniki_core_threadAddFollowup($ciniki, 'ciniki.bugs', 'followup', $args['tnid'], 
            'ciniki_bug_followups', 'ciniki_bug_history', 'bug', $bug_id, array(
            'user_id'=>$ciniki['session']['user']['id'],
            'bug_id'=>$bug_id,
            'content'=>$args['followup']
            ));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.bugs');
            return $rc;
        }
    }
    
    //
    // Add a private notes followup if they included details
    //
    if( isset($args['notesfollowup']) && $args['notesfollowup'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollowup');
        $rc = ciniki_core_threadAddFollowup($ciniki, 'ciniki.bugs', 'followup', $args['tnid'], 
            'ciniki_bug_notes', 'ciniki_bug_history', 'bug', $bug_id, array(
            'user_id'=>$ciniki['session']['user']['id'],
            'bug_id'=>$bug_id,
            'content'=>$args['notesfollowup']
            ));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.bugs');
            return $rc;
        }
    }
    
    //
    // Attach the user to the ciniki_bug_users as a follower
    // $ciniki, $module, $prefix, {$prefix}_id, settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
    $rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.bugs', 'user', $args['tnid'], 
        'ciniki_bug_users', 'ciniki_bug_history', 
        'bug', $bug_id, $ciniki['session']['user']['id'], (0x01));
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.bugs');
        return $rc;
    }

    //
    // Add users who were assigned.  If the creator also is assigned the atdo, then they will be 
    // both a follower (above code) and assigned (below code).
    // Add the viewed flag to be set, so it's marked as unread for new assigned users.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
    if( isset($args['assigned']) && is_array($args['assigned']) ) {
        foreach( $args['assigned'] as $user_id ) {
            $rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.bugs', 'user', $args['tnid'], 
                'ciniki_bug_users', 'ciniki_bug_history', 'bug', $bug_id, $user_id, (0x02));
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.bugs');
                return $rc;
            }
        }
    }

    //
    // FIXME: Attach tenant users who are bug trackers
    //
    if( ($args['type'] == 1 && isset($settings['bugs.add.attach.group.users']) && $settings['bugs.add.attach.group.users'] == 'yes')
        || ($args['type'] == 2 && isset($settings['features.add.attach.group.users']) && $settings['features.add.attach.group.users'] == 'yes')
        ) {
        //
        // Select the users attached to the tenant and bug tracking module
        //
        
        // threadAddFollower($ciniki, 'bugs', 'bug', $bug_id, $user_id, array());
    }

    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.bugs');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'bugs');

    //
    // FIXME: Check the settings to see if there's anybody who should be auto attached and emailed
    //
    if( ($args['type'] == 1 && isset($settings['bugs.add.notify.owners']) && $settings['bugs.add.notify.owners'] == 'yes')
        || ($args['type'] == 2 && isset($settings['features.add.notify.owners']) && $settings['features.add.notify.owners'] == 'yes')
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        //
        //  Email the owners a bug was added to the system.
        //
        $strsql = "SELECT user_id FROM ciniki_tenant_users "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND package = 'ciniki' "
            . "AND permission_group = 'owners' "
            . "AND status = 10 "
            . "";
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.bugs', 'user_ids', 'user_id');
        if( $rc['stat'] != 'ok' || !isset($rc['user_ids']) || !is_array($rc['user_ids']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.bugs.6', 'msg'=>'Unable to find users', 'err'=>$rc['err']));
        }
        
        foreach($rc['user_ids'] as $user_id) {
            // 
            // Don't email the submitter, they will get a separate email
            //
            if( $user_id != $ciniki['session']['user']['id'] ) {
                $ciniki['emailqueue'][] = array('user_id'=>$user_id,
                    'subject'=>$ciniki['session']['user']['display_name'] . ' submitted bug #' . $bug_id . ': ' . $args['subject'],
                    'textmsg'=>($args['followup']!=''?$args['followup']:'No details'),
                    );
            }
        }
    }

    //
    // Send an email to the person who submitted the bug, so they know it has been received
    //
    if( $email_submitter == 'yes' ) {
        $ciniki['emailqueue'][] = array('user_id'=>$ciniki['session']['user']['id'],
            'subject'=>'Bug #' . $bug_id . ': ' . $args['subject'] . ' submitted',
            'textmsg'=>'Thank you for submitting a bug/feature request.  I have alerted the appropriate people and we will look into it.',
            );
    }

    //
    // Other email alerts for bug submission
    //
    if( $args['type'] == 1 && isset($settings['bugs.add.notify.sms.email']) && $settings['bugs.add.notify.sms.email'] != '' ) {
        //  
        // The from address can be set in the config file.
        //  
        $emails = preg_split('/,/', $settings['bugs.add.notify.sms.email']);
        foreach($emails as $email) {
            if( $email != '' ) {
                $ciniki['emailqueue'][] = array('to'=>$settings['bugs.add.notify.sms.email'],
                    'subject'=>'New Bug #' . $bug_id,
                    'textmsg'=>$ciniki['session']['user']['display_name'] . ':' . $args['subject'],
                    );
            }
        }
    }

    return array('stat'=>'ok', 'id'=>$bug_id);
}
?>
