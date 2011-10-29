<?php
//
// Description
// -----------
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_bugs_checkAccess($ciniki, $business_id, $method, $bug_id, $user_id) {

	//
	// Load the rulesets for this module
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/getRulesets.php');
	$rulesets = ciniki_bugs_getRuleSets($ciniki);

	//
	// Check if the module is turned on for the business
	// Check the business is active
	// Get the ruleset for this module
	//
	$strsql = "SELECT ruleset FROM businesses, business_permissions "
		. "WHERE businesses.id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND businesses.status = 1 "
		. "AND (businesses.modules & 0x0800) = 0x0800 "
		. "AND businesses.id = business_permissions.business_id "
		. "AND business_permissions.module = 'bugs' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'module');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['module']) || !isset($rc['module']['ruleset']) || $rc['module']['ruleset'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'203', 'msg'=>'Access denied.'));
	}

	//
	// Check to see if the ruleset is valid
	//
	if( !isset($rulesets[$rc['module']['ruleset']]) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'204', 'msg'=>'Access denied.'));
	}
	$ruleset = $rc['module']['ruleset'];

	// 
	// Get the rules for the specified method
	//
	$rules = array();
	if( isset($rulesets[$ruleset]['methods']) && isset($rulesets[$ruleset]['methods'][$method]) ) {
		// If there is a specific ruleset for the requested method
		$rules = $rulesets[$ruleset]['methods'][$method];
	} elseif( isset($rulesets[$ruleset]['default']) ) {
		// The default ruleset for all methods if not specified
		$rules = $rulesets[$ruleset]['default'];
	} else {
		return array('stat'=>'fail', 'err'=>array('code'=>'205', 'msg'=>'Access denied.'));
	}


	//
	// Apply the rules.  Any matching rule will allow access.
	//


	//
	// If business_group specified, check the session user in the business_users table.
	//
	if( isset($rules['business_group']) && $rules['business_group'] > 0 ) {
		//
		// Compare the session users bitmask, with the bitmask specified in the rules
		// If when OR'd together, any bits are set, they have access.
		//
		$strsql = sprintf("SELECT business_id, user_id FROM business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND (groups & 0x%x) > 0 ", ciniki_core_dbQuote($ciniki, $rules['business_group']));
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'user');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		//
		// Double check business_id and user_id match, for single row returned.
		//
		if( isset($rc['user']) && isset($rc['user']['business_id']) 
			&& $rc['user']['business_id'] == $business_id 
			&& $rc['user']['user_id'] = $ciniki['session']['user']['id'] ) {
			// Access Granted!
			return array('stat'=>'ok');
		}
	}

	//
	// When dealing with the master business, a customer can be any business employee from
	// any active business.  This allows them to submit bugs via ciniki-manage.
	//
	if( isset($rules['customer']) && $rules['customer'] == 'any' && $ciniki['config']['core']['master_business_id'] == $business_id ) {
		$strsql = "SELECT user_id FROM business_users, businesses "
			. "WHERE business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND business_users.business_id = businesses.id "
			. "AND businesses.status = 1 ";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'user');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_rows'] > 0 ) {
			return array('stat'=>'ok');
		}
	} 
	
	// 
	// Check if the session user is a customer of the business
	//
	if( isset($rules['customer']) && $rules['customer'] == 'any' ) {
		// FIXME: finish, there is currently no link between customers and users.  When that is in place, this will work.
	//	$strsql = "SELECT * FROM customers "
	//		. "WHERE customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "'"
	//		. "AND customers.user_id = ";
	//	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'user');
	//	if( $rc['stat'] != 'ok' ) {
	//		return $rc;
	//	}
	}

	//
	// When checking the rule 'customer'=>'self', the requested method can only be done
	// if the customer making the request is requesting it for themselves.  They can't
	// call the method for another user_id.
	//
	if( isset($rules['customer']) && $rules['customer'] == 'self' && $ciniki['session']['user']['id'] == $user_id ) {
		return array('stat'=>'ok');
	}

	if( isset($rules['customer']) && $rules['customer'] == 'bug_user' ) {
		$strsql = "SELECT bug_users.user_id FROM bugs, bug_users "
			. "WHERE bugs.id = '" . ciniki_core_dbQuote($ciniki, $bug_id) . "' "
			. "AND bugs.id = bug_users.bug_id "
			. "AND bug_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'bugs', 'user');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		// If the user is attached to the bug, they have permission for the method
		if( isset($rc['user']) && isset($rc['user']['user_id']) && $rc['user']['user_id'] == $ciniki['session']['user']['id'] ) {
			return array('stat'=>'ok');
		}
	}
	
	//
	// By default, fail
	//
	return array('stat'=>'fail', 'err'=>array('code'=>'207', 'msg'=>'Access denied.'));
}
?>
