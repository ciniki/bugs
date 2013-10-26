<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_bugs_objects($ciniki) {
	
	$objects = array();
	$objects['bug'] = array(
		'name'=>'Bug',
		'sync'=>'yes',
		'table'=>'ciniki_bugs',
		'fields'=>array(
			'user_id'=>array('ref'=>'ciniki.users.user'),
			'subject'=>array(),
			'type'=>array(),
			'priority'=>array(),
			'status'=>array(),
			'category'=>array(),
			'source'=>array(),
			'source_link'=>array(),
			'options'=>array(),
			),
		'history_table'=>'ciniki_bug_history',
		);
	$objects['followup'] = array(
		'name'=>'Bug Followup',
		'sync'=>'yes',
		'table'=>'ciniki_bug_followups',
		'fields'=>array(
			'parent_id'=>array(),
			'bug_id'=>array('ref'=>'ciniki.bugs.bug'),
			'user_id'=>array('ref'=>'ciniki.users.user'),
			'content'=>array(),
			),
		'history_table'=>'ciniki_bug_history',
		);
	$objects['note'] = array(
		'name'=>'Bug Note',
		'sync'=>'yes',
		'table'=>'ciniki_bug_notes',
		'fields'=>array(
			'parent_id'=>array(),
			'bug_id'=>array('ref'=>'ciniki.bugs.bug'),
			'user_id'=>array('ref'=>'ciniki.users.user'),
			'content'=>array(),
			),
		'history_table'=>'ciniki_bug_history',
		);
	$objects['user'] = array(
		'name'=>'Bug User',
		'sync'=>'yes',
		'table'=>'ciniki_bug_users',
		'fields'=>array(
			'bug_id'=>array('ref'=>'ciniki.bugs.bug'),
			'user_id'=>array('ref'=>'ciniki.users.user'),
			'perms'=>array(),
			),
		'history_table'=>'ciniki_bug_history',
		);
	$objects['setting'] = array(
		'type'=>'settings',
		'name'=>'Bug Settings',
		'table'=>'ciniki_bug_settings',
		'history_table'=>'ciniki_bug_history',
		);

	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
