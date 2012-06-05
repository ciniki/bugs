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
// business_id: 		The business the bug is attached to.
// name:				The very brief bug description.
// bug_text:			(optional) The longer description or additional details for the bug.
// 
// Returns
// -------
// <rsp stat='ok' id='1' />
//
function ciniki_bugs_add($ciniki) {
	//
	// Track if the submitter should be emailed, if submitter is owner, we don't want to email twice
	// 
	$email_submitter = 'yes';
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'type'=>array('required'=>'no', 'blank'=>'no', 'default'=>'1', 'errmsg'=>'No type specified'), 
//		'state'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'Must specify Open or Closed',
//			'accepted'=>array('Open', 'Closed')), 
		'subject'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No subject specified'), 
		'priority'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>''), 
		'status'=>array('required'=>'no', 'default'=>'1', 'blank'=>'no', 'errmsg'=>''), 
		'category'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>''), 
		'source'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>''), 
		'source_link'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>''), 
		'content'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No follow up specified'), 
		'assigned'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'errmsg'=>'No assignments specified'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Get the module settings
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/getSettings.php');
	$rc = ciniki_bugs_getSettings($ciniki, $args['business_id'], 'ciniki.bugs.add');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/checkAccess.php');
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.add', 0, 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Setup the other arguments required for adding a thread.  These are arguments
	// which should not come through the API, but be set within the API code.
	//
	$args['options'] = 0x03;
	$args['user_id'] = $ciniki['session']['user']['id'];

	//
	// Turn off autocommit
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Add the bug to the database using the thread libraries
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	$strsql = "INSERT INTO ciniki_bugs (business_id, type, priority, status, category, user_id, subject, "
		. "source, source_link, options, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['priority']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['category']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['source']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['source_link']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['options']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'bugs');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'bugs');
		return $rc;
	}
	$bug_id = $rc['insert_id'];
	if( $bug_id < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'215', 'msg'=>'Internal Error', 'pmsg'=>'Unable to add bug.'));
	}

	//
	// Add a followup if they included details
	//
	if( isset($ciniki['request']['args']['content']) && $ciniki['request']['args']['content'] != '' ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddFollowup.php');
		$rc = ciniki_core_threadAddFollowup($ciniki, 'bugs', 'ciniki_bug_followups', 'bug', $bug_id, array(
			'user_id'=>$ciniki['session']['user']['id'],
			'bug_id'=>$bug_id,
			'content'=>$ciniki['request']['args']['content']
			));
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'bugs');
			return $rc;
		}
	}
	
	//
	// Attach the user to the ciniki_bug_users as a follower
	// $ciniki, $module, $prefix, {$prefix}_id, settings
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddFollower.php');
	$rc = ciniki_core_threadAddFollower($ciniki, 'bugs', 'ciniki_bug_users', 'bug', $bug_id, $ciniki['session']['user']['id']);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'bugs');
		return $rc;
	}

	//
	// Add users who were assigned.  If the creator also is assigned the atdo, then they will be 
	// both a follower (above code) and assigned (below code).
	// Add the viewed flag to be set, so it's marked as unread for new assigned users.
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddUserPerms.php');
	if( isset($args['assigned']) && is_array($args['assigned']) ) {
		foreach( $args['assigned'] as $user_id ) {
			$rc = ciniki_core_threadAddUserPerms($ciniki, 'bugs', 'ciniki_bug_users', 'bug', $bug_id, $user_id, (0x02));
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'bugs');
				return $rc;
			}
		}
	}

	//
	// FIXME: Attach business users who are bug trackers
	//
	if( ($args['type'] == 1 && isset($settings['bugs.add.attach.group.users']) && $settings['bugs.add.attach.group.users'] == 'yes')
		|| ($args['type'] == 2 && isset($settings['features.add.attach.group.users']) && $settings['features.add.attach.group.users'] == 'yes')
		) {
		//
		// Select the users attached to the business and bug tracking module
		//
		$strsql = "SELECT user_id FROM ciniki_business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (groups & 0x400) = 0x0400 ";
		
		// threadAddFollower($ciniki, 'bugs', 'bug', $bug_id, $user_id, array());
	}

	//
	// FIXME: Add tags
	//
	if( isset($ciniki['request']['args']['tags']) && $ciniki['request']['args']['tags'] != '' ) {
		// require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddTags.php');
		// threadAddTags($ciniki, 'bugs', 'bug', $bug_id);
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// FIXME: Check the settings to see if there's anybody who should be auto attached and emailed
	//
	if( ($args['type'] == 1 && isset($settings['bugs.add.notify.owners']) && $settings['bugs.add.notify.owners'] == 'yes')
		|| ($args['type'] == 2 && isset($settings['features.add.notify.owners']) && $settings['features.add.notify.owners'] == 'yes')
		) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQueryList.php');
		require_once($ciniki['config']['core']['modules_dir'] . '/users/private/emailUser.php');
		//
		//	Email the owners a bug was added to the system.
		//
		$strsql = "SELECT user_id FROM ciniki_business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (groups & 0x01) > 0 ";
		$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'bugs', 'user_ids', 'user_id');
		if( $rc['stat'] != 'ok' || !isset($rc['user_ids']) || !is_array($rc['user_ids']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'206', 'msg'=>'Unable to find users', 'err'=>$rc['err']));
		}
		
		foreach($rc['user_ids'] as $user_id) {
			// 
			// Don't email the submitter, they will get a separate email
			//
			if( $user_id != $ciniki['session']['user']['id'] ) {
				$rc = ciniki_users_emailUser($ciniki, $user_id, 
					$ciniki['session']['user']['display_name'] . ' submitted bug #' . $bug_id . ': ' . $args['subject'],
						$args['followup'] 
						. "\n\n"
					);
			}
		}
	}

	//
	// Send an email to the person who submitted the bug, so they know it has been received
	//
	if( $email_submitter == 'yes' ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/users/private/emailUser.php');
		$rc = ciniki_users_emailUser($ciniki, $ciniki['session']['user']['id'], 
			'Bug #' . $bug_id . ': ' . $args['subject'] . ' submitted',
				'Thank you for submitting a bug/feature request.  I have alerted the approriate people and we will look into it.'
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
				$headers = 'From: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n";
				mail($settings['bugs.add.notify.sms.email'], 'New Bug #' . $bug_id, $args['subject'], $headers, '-f' . $ciniki['config']['core']['system.email']);	
			}
		}
	}

	return array('stat'=>'ok', 'id'=>$bug_id);
}
?>
