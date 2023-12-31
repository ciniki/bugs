<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get bugs for.
//
// Returns
// -------
//
function ciniki_bugs_hooks_uiSettings($ciniki, $tnid, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'settings'=>array(), 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Get the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_bug_settings', 'tnid', $tnid, 'ciniki.bugs', 'settings', '');
    if( $rc['stat'] == 'ok' && isset($rc['settings']) ) {
        $rsp['settings'] = $rc['settings'];
    }

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['ciniki.bugs'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5300,
            'label'=>'Bug Tracking', 
            'edit'=>array('app'=>'ciniki.bugs.main'),
            );
        $rsp['menu_items'][] = $menu_item;

    } 

    if( isset($ciniki['tenant']['modules']['ciniki.bugs'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>5300, 'label'=>'Bug Tracker', 'edit'=>array('app'=>'ciniki.bugs.settings'));
    }

    return $rsp;
}
?>
