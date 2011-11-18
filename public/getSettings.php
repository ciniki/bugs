<?php
//
// Description
// -----------
// This will return the list of settings for the bugs module for a business.
//
// Info
// ----
// Status: 			started
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_bugs_getSettings($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/checkAccess.php');
    $rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.getSettings', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	
	//
	// Grab the settings for the business from the database
	//
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQuery.php');
	$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_bug_settings', 'business_id', $args['business_id'], 'bugs', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Return the response, including colour arrays and todays date
	//
	return $rc;
}
?>
