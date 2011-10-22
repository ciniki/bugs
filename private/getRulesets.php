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
	// Permissions can be in the form of=> 
	//		- owners, any employee in the group 0x0001 (owner) in business_users.
	//		- group, any employee in the group 0x0400 (bug tracker) in business_users.
	//		- employee, any employee in the group 0x0002 (employee) in business_users.
	//		- employees, customer, customers
	//
	// - business_group - 0x0401, (any owners) or (employees in group Bug Tracker)
	// - business_group - 0x0403, (any owners) or (any employees) or (employees in group Bug Tracker)
	// - business_group - blank/non-existent, ignored
	//
	// business_group rules are OR'd together with customers rules
	//
	// - customers - 'any', (any customers of the business)
	// - customers - 'self', (the session user_id must be the same as requested user_id)
	// - customers - 'bug_user', (the session user_id must be the same as user_id who started the bug)
	//
	// *note* A function can only be allowed to customers, if there is no business_group rule.
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
			'default'=>array('business_group'=>0x0401),
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
			'default'=>array('business_group'=>0x0401),
			'methods'=>array(
				'ciniki.bugs.assign'=>array('business_group'=>0x0401),
				'ciniki.bugs.close'=>array('business_group'=>0x0401),
				'ciniki.bugs.removeTag'=>array('business_group'=>0x0401),

				'ciniki.bugs.add'=>array('customer'=>'any', 'business_group'=>0x0403),
				'ciniki.bugs.addFollowup'=>array('customer'=>'any', 'business_group'=>0x0403),		
				'ciniki.bugs.getFollowups'=>array('customer'=>'any', 'business_group'=>0x0403),
				'ciniki.bugs.getList'=>array('customer'=>'any', 'business_group'=>0x0403),
				'ciniki.bugs.get'=>array('customer'=>'any', 'business_group'=>0x0403),
				'ciniki.bugs.getSources'=>array('customer'=>'any', 'business_group'=>0x0403),
				'ciniki.bugs.getStates'=>array('customer'=>'any', 'business_group'=>0x0403),
				'ciniki.bugs.getTags'=>array('customer'=>'any', 'business_group'=>0x0403),			

				// Any customer can subscribe to follow a bug
				'ciniki.bugs.subscribe'=>array('customer'=>'any', 'business_group'=>0x0403),		

				// Only if the customer started the bug, can they add a tag.
				// Owners and employees in the Bug tracking group can add tags to any bug
				'ciniki.bugs.addTag'=>array('customer'=>'bug_user', 'business_group'=>0x0403),	

				// Any customer can unsubscribe themselves from a thread
				'ciniki.bugs.unsubscribe'=>array('customer'=>'self', 'business_group'=>0x0403), 	

				'ciniki.bugs.search'=>array('customer'=>'any', 'business_group'=>0x0403),
				)
			),
	);
}
?>
