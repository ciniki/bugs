<?php
//
// Description
// -----------
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
	$strsql = "SELECT ruleset FROM ciniki_businesses, ciniki_business_modules "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_businesses.status = 1 "														// Business is active
		. "AND ciniki_businesses.id = ciniki_business_modules.business_id "
		. "AND ciniki_business_modules.package = 'ciniki' "
		. "AND ciniki_business_modules.module = 'bugs' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'module');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['module']) || !isset($rc['module']['ruleset']) || $rc['module']['ruleset'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'203', 'msg'=>'Access denied.'));
	}

	//
	// Check to see if the ruleset is valid
	//
	if( !isset($rulesets[$rc['module']['ruleset']]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'204', 'msg'=>'Access denied.'));
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'205', 'msg'=>'Access denied.'));
	}


	//
	// Apply the rules.  Any matching rule will allow access.
	//

	//
	// If permissions_group specified, check the session user in the business_users table.
	//
	if( isset($rules['permission_groups']) && $rules['permission_groups'] > 0 ) {
		//
		// If the user is attached to the business AND in the one of the accepted permissions group, they will be granted access
		//
		$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND CONCAT_WS('.', package, permission_group) IN ('" . implode("','", $rules['permission_groups']) . "') "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'user');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'514', 'msg'=>'Access denied.', 'err'=>$rc['err']));
		}
		
		//
		// If the user has permission, return ok
		//
		if( isset($rc['rows']) && isset($rc['rows'][0]) 
			&& $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
			return array('stat'=>'ok');
		}
	}

	//
	// When dealing with the master business, a customer can be any business employee from
	// any active business.  This allows them to submit bugs via ciniki-manage.
	//
	if( isset($rules['customer']) && $rules['customer'] == 'any' && $ciniki['config']['core']['master_business_id'] == $business_id ) {
		$strsql = "SELECT user_id FROM ciniki_business_users, ciniki_businesses "
			. "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND ciniki_business_users.business_id = ciniki_businesses.id "
			. "AND ciniki_businesses.status = 1 ";
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
	//	$strsql = "SELECT * FROM ciniki_customers "
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
		$strsql = "SELECT ciniki_bug_users.user_id FROM ciniki_bugs, ciniki_bug_users "
			. "WHERE ciniki_bugs.id = '" . ciniki_core_dbQuote($ciniki, $bug_id) . "' "
			. "AND ciniki_bugs.id = ciniki_bug_users.bug_id "
			. "AND ciniki_bug_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
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
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'207', 'msg'=>'Access denied.'));
}
?>
