<?php
//
// Description
// -----------
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_bugs_checkAccess($ciniki, $tnid, $method, $bug_id, $user_id) {
    //
    // Check if the tenant is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $tnid, 'ciniki', 'bugs');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($rc['ruleset']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.bugs.1', 'msg'=>'No permissions granted'));
    }
    $modules = $rc['modules'];

    //
    // Load the rulesets for this module
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'getRulesets');
    $rulesets = ciniki_bugs_getRuleSets($ciniki);

    //
    // Check to see if the ruleset is valid
    //
    if( !isset($rulesets[$rc['ruleset']]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.bugs.2', 'msg'=>'Access denied.'));
    }
    $ruleset = $rc['ruleset'];

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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.bugs.3', 'msg'=>'Access denied.'));
    }


    //
    // Apply the rules.  Any matching rule will allow access.
    //

    //
    // If permissions_group specified, check the session user in the tenant_users table.
    //
    if( isset($rules['permission_groups']) && $rules['permission_groups'] > 0 ) {
        //
        // If the user is attached to the tenant AND in the one of the accepted permissions group, they will be granted access
        //
        $strsql = "SELECT tnid, user_id FROM ciniki_tenant_users "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND status = 10 "
            . "AND CONCAT_WS('.', package, permission_group) IN ('" . implode("','", $rules['permission_groups']) . "') "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.bugs.4', 'msg'=>'Access denied.', 'err'=>$rc['err']));
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
    // When dealing with the master tenant, a customer can be any tenant employee from
    // any active tenant.  This allows them to submit bugs via ciniki-manage.
    //
    if( isset($rules['customer']) && $rules['customer'] == 'any' && $ciniki['config']['core']['master_tnid'] == $tnid ) {
        $strsql = "SELECT user_id FROM ciniki_tenant_users, ciniki_tenants "
            . "WHERE ciniki_tenant_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND ciniki_tenant_users.tnid = ciniki_tenants.id "
            . "AND ciniki_tenants.status = 1 ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'ok');
        }
    } 
    
    // 
    // Check if the session user is a customer of the tenant
    //
    if( isset($rules['customer']) && $rules['customer'] == 'any' ) {
        // FIXME: finish, there is currently no link between customers and users.  When that is in place, this will work.
    //  $strsql = "SELECT * FROM ciniki_customers "
    //      . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "'"
    //      . "AND customers.user_id = ";
    //  $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
    //  if( $rc['stat'] != 'ok' ) {
    //      return $rc;
    //  }
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
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.bugs', 'user');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        // If the user is attached to the bug, they have permission for the method
        if( isset($rc['user']) && isset($rc['user']['user_id']) && $rc['user']['user_id'] == $ciniki['session']['user']['id'] ) {
            return array('stat'=>'ok');
        }
    }
    
    //
    // Sysadmins are allowed full access
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok');
    }

    //
    // By default, fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.bugs.5', 'msg'=>'Access denied.'));
}
?>
