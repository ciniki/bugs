<?php
//
// Description
// -----------
// This function will return the array of rulesets available to the bugs modules,
// and all the information for them.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_bugs_getRulesets($ciniki) {

	//
	// permission_groups rules are OR'd together with customers rules
	//
	// - customers - 'any', (any customers of the business)
	// - customers - 'self', (the session user_id must be the same as requested user_id)
	// - customers - 'bug_user', (the session user_id must be the same as user_id who started the bug)
	//
	// *note* A function can only be allowed to customers, if there is no permission_groups rule.
	//

	return array(
		//
		// The default for nothing selected is to have access restricted to nobody
		//
		''=>array('label'=>'Nobody',
			'description'=>'Nobody has access, no even owners.',
			'details'=>array(
				'owners'=>'no access.',
				'employees'=>'no access.',
				'customers'=>'no access.'
				),
			'default'=>array(),
			'methods'=>array()
			),
		//
		// For all methods, you must be in the group Bug Tracker.  Only need to specify
		// the default permissions, will automatically be applied to all methods.
		//
		'group_restricted'=>array('label'=>'Group Restricted', 
			'description'=>'This permission setting is recommended for tracking bugs '
				. 'of internal projects.  No customers will have access to view, submit or '
				. 'comment on any bugs.  Only the business owners and employees assigned '
				. 'to the Bug Tracking group have full access to the bugs, all other employees '
				. 'will be denied access.',
			'details'=>array(
				'owners'=>'all tasks on all bugs.',
				'employees'=>'all tasks on all bugs if assigned to group Bug Tracking.',
				'customers'=>'no access.'
				),
			'default'=>array('permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),
			'methods'=>array()
			),

		//
		// Any customer can add, view, search for bugs.  
		// Only Owners/Bug Trackers can add followups, close and assign
		//
		'all_customers'=>array('label'=>'All Customers, Group Managed', 
			'description'=>'This setting is good if you would like to offer bug tracking for '	
				. 'your product(s) via  your website to your customers.  All customers who '
				. 'create an account will be able to submit, view and comment on any bugs. '
				. 'Management of open/closed bugs, and assignments can be done by the '
				. 'business owner or any employee who as been assigned to the Bug Tracking group.'
				. '',
			'details'=>array(
				'owners'=>'all tasks on all bugs.',
				'employees'=>'only those assigned to the Bug Tracking group, all tasks on all bugs.',
				'customers'=>'add, followup, view all bugs.'
				),
			'default'=>array('permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),
			'methods'=>array(
				// Only business users assigned to bugs group, or the owner can call these methods
				// Employee's not in the bugs group can't call these methods
				'ciniki.bugs.bugAssign'=>array('permission_groups'=>array('ciniki.owners', 'ciniki.bugs')),
				'ciniki.bugs.bugClose'=>array('permission_groups'=>array('ciniki.owners', 'ciniki.bugs')),
				'ciniki.bugs.bugRemoveTag'=>array('permission_groups'=>array('ciniki.owners', 'ciniki.bugs')),
				'ciniki.bugs.bugGet'=>array('permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),

				// any customer of the business, or employee or the owner
				// employee's don't have to be in the bugs group to call these methods, but it's also allowed
				'ciniki.bugs.bugAdd'=>array('customer'=>'any', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),
				'ciniki.bugs.bugAddFollowup'=>array('customer'=>'any', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),		
				'ciniki.bugs.bugGetFollowups'=>array('customer'=>'any', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),
				'ciniki.bugs.bugList'=>array('customer'=>'any', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),
				'ciniki.bugs.bugGetSources'=>array('customer'=>'any', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),
				'ciniki.bugs.bugGetStates'=>array('customer'=>'any', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),
				'ciniki.bugs.bugGetTags'=>array('customer'=>'any', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),			

				// Any customer can subscribe to follow a bug
				'ciniki.bugs.bugSubscribe'=>array('customer'=>'any', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),		

				// Only if the customer started the bug, can they add a tag.
				// Owners and employees in the Bug tracking group can add tags to any bug
				'ciniki.bugs.bugAddTag'=>array('customer'=>'bug_user', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),	

				// Any customer can unsubscribe themselves from a thread
				'ciniki.bugs.bugUnsubscribe'=>array('customer'=>'self', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')), 	

				'ciniki.bugs.bugSearch'=>array('customer'=>'any', 'permission_groups'=>array('ciniki.owners', 'ciniki.employees', 'ciniki.bugs')),
				)
			),
	);
}
?>
