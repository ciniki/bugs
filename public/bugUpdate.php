<?php
//
// Description
// -----------
// Update bug details
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_bugs_bugUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'bug_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Bug'), 
		'type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type', 'validlist'=>array('1', '2', '3')),
		'priority'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type', 'validlist'=>array('10','30','50')),
		'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status', 'validlist'=>array('1', '60')),
        'subject'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Subject'), 
        'category'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Category'), 
        'options'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Flags'), 
		'assigned'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Assigned'),
        'followup'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Followup'), 
        'notesfollowup'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'checkAccess');
    $rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.bugUpdate', $args['bug_id'], $ciniki['session']['user']['id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.bugs');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.bugs.bug', $args['bug_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check if the assigned users has changed
	//
	if( isset($args['assigned']) && is_array($args['assigned']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadRemoveUserPerms');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
		//
		// Get the list of currently assigned users
		//
		$strsql = "SELECT user_id "
			. "FROM ciniki_bug_users "
			. "WHERE bug_id = '" . ciniki_core_dbQuote($ciniki, $args['bug_id']) . "' "
			. "AND (perms&0x02) = 2 "
			. "";
		$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.bugs', 'users', 'user_id');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'681', 'msg'=>'Unable to load bug user information', 'err'=>$rc['err']));
		}
		$task_users = $rc['users'];
		// 
		// Remove users no longer assigned
		//
		$to_be_removed = array_diff($task_users, $args['assigned']);
		if( is_array($to_be_removed) ) {
			foreach($to_be_removed as $user_id) {
				$rc = ciniki_core_threadRemoveUserPerms($ciniki, 'ciniki.bugs', 'user', 
					$args['business_id'], 'ciniki_bug_users', 'ciniki_bug_history', 
					'bug', $args['bug_id'], $user_id, 0x02);
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'682', 'msg'=>'Unable to update bug user information', 'err'=>$rc['err']));
				}
			}
		}
		$to_be_added = array_diff($args['assigned'], $task_users);
		if( is_array($to_be_added) ) {
			foreach($to_be_added as $user_id) {
				$rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.bugs', 'user', 
					$args['business_id'], 'ciniki_bug_users', 'ciniki_bug_history',
					'bug', $args['bug_id'], $user_id, (0x02));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'683', 'msg'=>'Unable to update bug information', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// Check if there is a followup, but after we have adjusted the assigned users
	// so any new users get the unviewed flag set
	//
	if( isset($args['followup']) && $args['followup'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollowup');
		$rc = ciniki_core_threadAddFollowup($ciniki, 'ciniki.bugs', 'followup', $args['business_id'], 
			'ciniki_bug_followups', 'ciniki_bug_history', 'bug', $args['bug_id'], array(
			'user_id'=>$ciniki['session']['user']['id'],
			'bug_id'=>$args['bug_id'],
			'content'=>$args['followup']
			));
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.bugs');
			return $rc;
		}
	}

	//
	// Check if there is a private notes followup
	//
	if( isset($args['notesfollowup']) && $args['notesfollowup'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollowup');
		$rc = ciniki_core_threadAddFollowup($ciniki, 'ciniki.bugs', 'followup', $args['business_id'], 
			'ciniki_bug_notes', 'ciniki_bug_history', 'bug', $args['bug_id'], array(
			'user_id'=>$ciniki['session']['user']['id'],
			'bug_id'=>$args['bug_id'],
			'content'=>$args['notesfollowup']
			));
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.bugs');
			return $rc;
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'bugs');

	return array('stat'=>'ok');
}
?>
