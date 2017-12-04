//
function ciniki_bugs_settings() {
    this.toggleOptions = {'no':' No ', 'yes':' Yes '};

    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.main = new M.panel('Bug Tracker Settings',
            'ciniki_bugs_settings', 'main',
            'mc', 'medium', 'sectioned', 'ciniki.bugs.settings.main');
        this.main.sections = {
            'bugs':{'label':'When a bug is added', 'fields':{
                'bugs.add.notify.owners':{'label':'Notify owners', 'type':'toggle', 'toggles':this.toggleOptions},
                'bugs.add.notify.sms.email':{'label':'SMS Email', 'size':'small', 'type':'text'},
                'bugs.add.attach.group.users':{'label':'Auto attach users', 'size':'small', 'type':'toggle', 'toggles':this.toggleOptions},
            }},
            'features':{'label':'When a feature is added', 'fields':{
                'features.add.notify.owners':{'label':'Notify owners', 'type':'toggle', 'toggles':this.toggleOptions},
                'features.add.attach.group.users':{'label':'Auto attach users', 'size':'small', 'type':'toggle', 'toggles':this.toggleOptions},
            }},
            '_colours':{'label':'Colours', 'fields':{
                'colours.status.60':{'label':'Closed', 'type':'colour'},
                'colours.priority.10':{'label':'Low', 'type':'colour'},
                'colours.priority.30':{'label':'Medium', 'type':'colour'},
                'colours.priority.50':{'label':'High', 'type':'colour'},
            }},
        };
        this.main.fieldValue = function(s, i, d) { 
            return this.data[i];
        };

        //  
        // Callback for the field history
        //  
        this.main.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.bugs.settingsHistory', 'args':{'tnid':M.curTenantID, 'field':i}};
        };

        this.main.addButton('save', 'Save', 'M.ciniki_bugs_settings.saveSettings();');
        this.main.addClose('Cancel');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_bugs_settings', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.showMain(cb);
    }

    //
    // Grab the stats for the tenant from the database and present the list of orders.
    //
    this.showMain = function(cb) {
        var rsp = M.api.getJSONCb('ciniki.bugs.settingsGet', 
            {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_bugs_settings.main;
                p.data = rsp.settings;
                p.refresh();
                p.show(cb);
            });
    }

    this.saveSettings = function() {
        var c = this.main.serializeForm('no');
        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.bugs.settingsUpdate', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_bugs_settings.main.close();
                });
        } else {
            M.ciniki_bugs_settings.main.close();
        }
    }
}
