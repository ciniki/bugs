//
// This file contains the panels to manage bugs/features/questions
//
function ciniki_bugs_main() {
    this.statuses = {'1':'Open', '60':'Closed'};
    this.optionFlags = {
        '1':{'name':'Notify Creator'}, 
        '2':{'name':'Notify Followers'}, 
        '5':{'name':'Public'},
        };

    this.init = function() {
        //
        // The main menu panel for bugs
        //
        this.menu = new M.panel('Bugs/Features/Questions',
            'ciniki_bugs_main', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.bugs.main.menu');
        this.menu.data = null;
        this.menu.sections = {
            'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':3, 'livesearchempty':'no', 'hint':'search',
                'noData':'No bugs or features found',
                'headerValues':null,
                'cellClasses':['','multiline','multiline'],
                },
            'latest':{'label':'Bugs', 'type':'simplegrid', 'num_cols':3, 'sortable':'yes',
                'headerValues':['', 'ID','Subject'],
                'sortTypes':['', 'number','text', ''],
                'cellClasses':['', 'multiline', 'multiline', ''],
                'noData':'No bugs found',
            },
            'bug_priorities':{'label':'Bugs', 'type':'simplelist'},
            'bug_categories':{'label':'', 'type':'simplelist'},
            'feature_priorities':{'label':'Features', 'type':'simplelist'},
            'feature_categories':{'label':'', 'type':'simplelist'},
            'question_priorities':{'label':'Questions', 'type':'simplelist'},
            'question_categories':{'label':'', 'type':'simplelist'},
            'unknown':{'label':'Other', 'visible':'no', 'type':'simplelist'},
        };
        this.menu.noData = function(s) { return 'No ' + this.sections[s].label; }
        this.menu.sectionData = function(s) { return this.data[s]; }
        this.menu.listValue = function(s, i, d) { 
            switch(s) {
                case 'bug_categories': 
                case 'feature_categories': 
                case 'question_categories': return d.category.name; 
                case 'bug_priorities': 
                case 'feature_priorities': 
                case 'question_priorities': return d.priority.name; 
            }
        };
        this.menu.listFn = function(s, i, d) { 
            if( s == 'bug_priorities' || s == 'feature_priorities' || s == 'question_priorities' ){
                return 'M.ciniki_bugs_main.showList(\'M.ciniki_bugs_main.showMenu();\',\'' + s + '\',null,\'' + encodeURIComponent(d.priority.name) + '\');'; 
            }
            return 'M.ciniki_bugs_main.showList(\'M.ciniki_bugs_main.showMenu();\',\'' + s + '\',\'' + encodeURIComponent(d.category.name) + '\');'; 
        };
        this.menu.listCount = function(s, i, d) { 
            switch(s) {
                case 'bug_categories': 
                case 'feature_categories': 
                case 'question_categories': return d.category.count; 
                case 'bug_priorities': 
                case 'feature_priorities': 
                case 'question_priorities': return d.priority.count; 
            }
        };
        this.menu.rowStyle = function(s, i, d) {
            if( s == 'bug_priorities' || s == 'feature_priorities' || s == 'question_priorities' ) {
                switch (d.priority.name) {
                    case 'Low': return 'background: ' + M.curTenant.bugs.settings['colours.priority.10'];
                    case 'Medium': return 'background: ' + M.curTenant.bugs.settings['colours.priority.30'];
                    case 'High': return 'background: ' + M.curTenant.bugs.settings['colours.priority.50'];
                }
            } else if( s == 'latest' ) {
                if( d.bug.status != '60' ) { 
                    if( d.bug.priority > 0 ) {
                        return 'background: ' + M.curTenant.bugs.settings['colours.priority.' + d.bug.priority]; 
                    }
                }
                else { return 'background: ' + M.curTenant.bugs.settings['colours.status.60']; }
            }
        };
        this.menu.cellValue = function(s, i, j, d) { 
            switch (j) {
                case 0: return '<span class="icon">' + M.curTenant.bugs.priorities[d.bug.priority] + '</span>';
//              case 1: return '<span class="maintext">' + d.bug.type + ' #' + d.bug.id + '</span><span class="subtext">' + d.bug.assigned_users + '</span>';   
                case 1: return '<span class="maintext">' + '#' + d.bug.id + '</span><span class="subtext">' + d.bug.type + '</span>';   
                case 2: return '<span class="maintext">' + d.bug.subject + '</span><span class="subtext">' + d.bug.source + ':' + d.bug.source_link + '</span>';    
                case 3: return d.bug.status_text;   
            }
            return '';
        }
        this.menu.rowFn = function(s, i, d) { return 'M.ciniki_bugs_main.showEdit(\'M.ciniki_bugs_main.showMenu();\',\'' + d.bug.id + '\');'; }
        // Live Search functions
        this.menu.liveSearchCb = function(s, i, v) {
            M.api.getJSONBgCb('ciniki.bugs.bugSearchQuick', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'15'},
                function(rsp) {
                    M.ciniki_bugs_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_bugs_main.menu.panelUID + '_' + s), rsp.bugs);
                });
            return true;
        };
        this.menu.liveSearchResultClass = function(s, f, i, j, d) {
            return this.sections[s].cellClasses[j];
        };
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            switch(j) {
                case 0: return '<span class="icon">' + M.curTenant.bugs.priorities[d.bug.priority] + '</span>';
                case 1: return '<span class="maintext">' + d.bug.type + ' #' + d.bug.id + '</span><span class="subtext">' + d.bug.assigned_users + '</span>';   
                case 2: return '<span class="maintext">' + d.bug.subject + '</span><span class="subtext">' + d.bug.source + ':' + d.bug.source_link + '</span>';    
            }
            return '';
        };
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
            return 'M.ciniki_bugs_main.showEdit(\'M.ciniki_bugs_main.showMenu();\', \'' + d.bug.id + '\');'; 
        };
        this.menu.liveSearchResultRowStyle = function(s, f, i, d) {
            if( d.bug.status != 'closed' ) { 
                if( d.bug.priority > 0 ) {
                    return 'background: ' + M.curTenant.bugs.settings['colours.priority.' + d.bug.priority]; 
                }
            }
            else { return 'background: ' + M.curTenant.bugs.settings['colours.status.60']; }
            return '';
        };
        this.menu.liveSearchSubmitFn = function(s, search_str) {
            M.ciniki_bugs_main.searchBugs('M.ciniki_bugs_main.showMenu();', search_str);
        };
        this.menu.addButton('add', 'Add', 'M.ciniki_bugs_main.showEdit(\'M.ciniki_bugs_main.showMenu();\',0,\'bug\');');
        this.menu.addClose('Back');
    
        //
        // The bug list panel
        //
        this.list = new M.panel('Bug List',
            'ciniki_bugs_main', 'list',
            'mc', 'medium','sectioned', 'ciniki.bugs.main.list');
        this.list.data = {};
        this.list.sections = {
            'open':{'label':'Bugs', 'type':'simplegrid', 'num_cols':3, 'sortable':'yes',
                'headerValues':['', 'ID','Subject'],
                'sortTypes':['', 'number','text', ''],
                'cellClasses':['', 'multiline', 'multiline', ''],
                'noData':'No bugs found',
            },
            'closed':{'label':'Recently Closed', 'type':'simplegrid', 'num_cols':3, 'sortable':'yes',
                'headerValues':['', 'ID','Subject','Status'],
                'sortTypes':['', 'number','text', ''],
                'cellClasses':['', 'multiline', 'multiline', ''],
                'noData':'No bugs found',
            },
        };
        this.list.sectionData = function(s) { return this.data[s]; }
        this.list.noData = function(s) { return this.sections[s].noData; }
        this.list.cellValue = function(s, i, j, d) { 
            switch (j) {
                case 0: return '<span class="icon">' + M.curTenant.bugs.priorities[d.bug.priority] + '</span>';
                case 1: return '<span class="maintext">' + d.bug.type + ' #' + d.bug.id + '</span><span class="subtext">' + d.bug.assigned_users + '</span>';   
                case 2: return '<span class="maintext">' + d.bug.subject + '</span><span class="subtext">' + d.bug.source + ':' + d.bug.source_link + '</span>';    
                case 3: return d.bug.status_text;   
            }
            return '';
        }
        this.list.rowStyle = function(s, i, d) {
            if( d.bug.status != '60' ) { 
                if( d.bug.priority > 0 ) {
                    return 'background: ' + M.curTenant.bugs.settings['colours.priority.' + d.bug.priority]; 
                }
            }
            else { return 'background: ' + M.curTenant.bugs.settings['colours.status.60']; }
            return '';
        };
        this.list.rowFn = function(s, i, d) { return 'M.ciniki_bugs_main.showEdit(\'M.ciniki_bugs_main.showList();\',\'' + d.bug.id + '\');'; }
        this.list.addButton('add', 'Add', 'M.ciniki_bugs_main.showEdit(\'M.ciniki_bugs_main.showList();\',0,M.ciniki_bugs_main.list.bug_type,M.ciniki_bugs_main.list.category);');
        this.list.addClose('Back');

        //
        // Bug panel
        //
        this.edit = new M.panel('Bug',
            'ciniki_bugs_main', 'edit',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.bugs.main.edit');
        this.edit.bug_id = 0;
        this.edit.data = null;
        this.edit.users = null;
        this.edit.forms = {};
        this.edit.formtab = 'bug';
        this.edit.formtabs = {'label':'', 'field':'type', 'tabs':{
            'bug':{'label':'Bug', 'field_id':1},
            'feature':{'label':'Feature', 'field_id':2},
            'question':{'label':'Question', 'field_id':3},
            }};
        this.edit.forms.bug = {
            'details':{'label':'', 'aside':'yes', 'hidelabel':'yes', 'fields':{
                'subject':{'label':'Subject', 'type':'text'},
                'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
                // FIXME: Add priority and status
                'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees, 'history':'yes'},
                'priority':{'label':'Priority', 'type':'multitoggle', 'toggles':M.curTenant.bugs.priorityText, 'history':'yes'},
                'status':{'label':'Status', 'type':'multitoggle', 'toggles':this.statuses},
                'options':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.optionFlags},
                }},
            'info':{'label':'Additional Information', 'aside':'yes', 'type':'simplelist', 'list':{
                'id':{'label':'Ticket #'},
                'user_display_name':{'label':'Submitted By'},
                'source':{'label':'Source'},
                'source_link':{'label':'Panel'},
                'date_added':{'label':'Opened'},
                'last_updated':{'label':'Updated'},
                'followers':{'label':'Followers'},
                }},
            'thread':{'label':'', 'type':'simplethread'},
            '_followup':{'label':'Add your response', 'fields':{
                'followup':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small', 'history':'no'},
                }},
            'notesthread':{'label':'Private Notes', 'type':'simplethread'},
            '_notes':{'label':'', 'fields':{
                'notesfollowup':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small', 'history':'no'},
                }},
            '_save':{'label':'', 'type':'simplebuttons', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_bugs_main.saveBug();'},
                }},
            };
        this.edit.forms.feature = {
            'details':{'label':'', 'hidelabel':'yes', 'aside':'yes', 'fields':{
                'subject':{'label':'Subject', 'type':'text'},
                'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
                'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees},
                'priority':{'label':'Priority', 'type':'multitoggle', 'toggles':M.curTenant.bugs.priorityText, 'history':'yes'},
                'status':{'label':'Status', 'type':'multitoggle', 'toggles':this.statuses, 'history':'yes'},
                'options':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.optionFlags},
                }},
            'info':{'label':'Additional Information', 'aside':'yes', 'type':'simplelist', 'list':{
                'id':{'label':'Ticket #'},
                'user_display_name':{'label':'Submitted By'},
//              'state':{'label':'State'},
                'source':{'label':'Source'},
                'source_link':{'label':'Panel'},
                'date_added':{'label':'Opened'},
                'last_updated':{'label':'Updated'},
                'followers':{'label':'Followers'},
                }},
            'thread':{'label':'', 'type':'simplethread'},
            '_followup':{'label':'Add your response', 'fields':{
                'followup':{'label':'Details', 'hidelabel':'yes', 'type':'textarea', 'size':'small', 'history':'no'},
                }},
            'notesthread':{'label':'Private Notes', 'type':'simplethread'},
            '_notes':{'label':'', 'fields':{
                'notesfollowup':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small', 'history':'no'},
                }},
            '_save':{'label':'', 'type':'simplebuttons', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_bugs_main.saveBug();'},
                }},
            };
        this.edit.forms.question = {
            'details':{'label':'', 'aside':'yes', 'hidelabel':'yes', 'fields':{
                'subject':{'label':'Subject', 'type':'text'},
                'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
                'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees},
                'priority':{'label':'Priority', 'type':'multitoggle', 'toggles':M.curTenant.bugs.priorityText, 'history':'yes'},
                'status':{'label':'Status', 'type':'multitoggle', 'toggles':this.statuses, 'history':'yes'},
                'options':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.optionFlags},
                }},
            'info':{'label':'Additional Information', 'aside':'yes', 'type':'simplelist', 'list':{
                'id':{'label':'Ticket #'},
                'user_display_name':{'label':'Submitted By'},
//              'state':{'label':'State'},
                'source':{'label':'Source'},
                'source_link':{'label':'Panel'},
                'date_added':{'label':'Opened'},
                'last_updated':{'label':'Updated'},
                'followers':{'label':'Followers'},
                }},
            'thread':{'label':'', 'type':'simplethread'},
            '_followup':{'label':'Add your response', 'fields':{
                'followup':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small', 'history':'no'},
                }},
            'notesthread':{'label':'Private Notes', 'type':'simplethread'},
            '_notes':{'label':'', 'fields':{
                'notesfollowup':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small', 'history':'no'},
                }},
            '_save':{'label':'', 'type':'simplebuttons', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_bugs_main.saveBug();'},
                }},
            };
        this.edit.sections = this.edit.forms.bug;
        this.edit.subject = '';
        this.edit.noData = function() { return 'Not yet implemented'; }
        this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
        this.edit.listLabel = function(s, i, d) { return d['label']; }
        this.edit.listValue = function(s, i, d) { 
            if( s == 'info' ) {
                if( i == 'assigned' ) {
                    var str = '';
                    for(var i=0;i<this.data['assigned'].length;i++) {
                        if( str == '' ) {
                            str = this.data['assigned'][i]['user']['display_name'];
                        } else {
                            str += ', ' + this.data['assigned'][i]['user']['display_name'];
                        }
                    }
                    return str;
                } else if( i == 'followers' ) {
                    var str = '';
                    for(var i=0;i<this.data['followers'].length;i++) {
                        if( str == '' ) {
                            str = this.data['followers'][i]['user']['display_name'];
                        } else {
                            str += ', ' + this.data['followers'][i]['user']['display_name'];
                        }
                    }
                    return str;
                }
                return this.data[i];
            }
        };
        this.edit.sectionData = function(s) {
            if( s == 'info' ) { return this.sections[s].list; }
            if( s == 'thread' ) { return this.data.followups; }
            if( s == 'notesthread' ) { return this.data.notes; }
        };
        this.edit.sectionLabel = function(s, d) { return d.label; }
        this.edit.threadSubject = function(s) { 
            if( s == 'thread' ) { return this.subject; }
            return null;
        };
        this.edit.threadFollowupUser = function(s, i, d) { return d.followup.user_display_name; }
        this.edit.threadFollowupAge = function(s, i, d) { return d.followup.age; }
        this.edit.threadFollowupContent = function(s, i, d) { return d.followup.content; }
        this.edit.liveSearchCb = function(s, i, value) {
            if( i == 'category' ) {
                var rsp = M.api.getJSONBgCb('ciniki.bugs.bugSearchField', {'tnid':M.curTenantID, 
                    'field':i, 'start_needle':value, 'limit':15}, function(rsp) {
                        M.ciniki_bugs_main.edit.liveSearchShow(s, i, M.gE(M.ciniki_bugs_main.edit.panelUID + '_' + i), rsp.results);
                    });
            }
        };
        this.edit.liveSearchResultValue = function(s, f, i, j, d) {
            if( d != null && d.result != null && d.result.name != null ) { return d.result.name; }
        };
        this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
            if( d.result != null ) {
                return 'M.ciniki_bugs_main.edit.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.result.name) + '\');';
            }
        };
        this.edit.updateField = function(s, fid, result) {
            M.gE(this.panelUID + '_' + fid).value = unescape(result);
            this.removeLiveSearch(s, fid);
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.bugs.bugHistory', 'args':{'tnid':M.curTenantID, 
                'bug_id':M.ciniki_bugs_main.edit.bug_id, 'field':i}};
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_bugs_main.saveBug();');
        this.edit.addClose('Back');

        //
        // The full search panel, which will search open and closed bugs/features
        //
        this.search = new M.panel('Search results',
            'ciniki_bugs_main', 'search',
            'mc', 'medium','sectioned', 'ciniki.bugs.main.search');
        this.search.data = {};
        this.search.sections = {
            'bugs':{'label':'Bugs/Features', 'type':'simplegrid', 'num_cols':3, 'sortable':'yes',
                'headerValues':['', 'ID','Subject','Status', 'Source', 'Source Link'],
                'sortTypes':['', 'number','text','text','text','text'],
                'cellClasses':['', 'multiline', 'multiline', ''],
                'noData':'No bugs found',
            },
            'closed':{'label':'Closed', 'type':'simplegrid', 'num_cols':3, 'sortable':'yes',
                'headerValues':['', 'ID','Subject','Status'],
                'sortTypes':['', 'number','text'],
                'cellClasses':['', 'multiline', 'multiline', ''],
                'noData':'No bugs found',
            },
        };
        this.search.sectionData = function(s) { return this.data[s]; }
        this.search.noData = function(s) { return this.sections[s].noData; }
        this.search.cellValue = function(s, i, j, d) { 
            switch (j) {
                case 0: return '<span class="icon">' + M.curTenant.bugs.priorities[d.bug.priority] + '</span>';
                case 1: return '<span class="maintext">' + d.bug.type + ' #' + d.bug.id + '</span><span class="subtext">' + d.bug.assigned_users + '</span>';   
                case 2: return '<span class="maintext">' + d.bug.subject + '</span><span class="subtext">' + d.bug.source + ':' + d.bug.source_link + '</span>';    
                case 3: return d.bug.status_text;   
            }
            return '';
        }
        this.search.rowStyle = function(s, i, d) {
            if( d.bug.status != '60' ) { 
                if( d.bug.priority > 0 ) {
                    return 'background: ' + M.curTenant.bugs.settings['colours.priority.' + d.bug.priority]; 
                }
            }
            else { return 'background: ' + M.curTenant.bugs.settings['colours.status.60']; }
            return '';
        };
        this.search.rowFn = function(s, i, d) { return 'M.ciniki_bugs_main.showEdit(\'M.ciniki_bugs_main.searchBugs();\',\'' + d.bug.id + '\');'; }
        this.search.addClose('Back');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_bugs_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.bug_id != null ) {
            this.showEdit(cb, args.bug_id);
        } else {
            this.showMenu(cb);
        }
    }

    this.showMenu = function(cb) {
        var rsp = M.api.getJSONCb('ciniki.bugs.bugStats', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_bugs_main.menu;
            p.data = {'bugs':{}, 'features':{}, 'questions':{}, 'unknown':{}};
            if( rsp.bugs != null ) { 
                p.sections.bug_priorities.visible = 'yes';
                p.sections.bug_categories.visible = 'yes';
                p.data.bug_priorities = rsp.bugs.priorities;
                p.data.bug_categories = rsp.bugs.categories;
            } else {
                p.sections.bug_priorities.visible = 'no';
                p.sections.bug_categories.visible = 'no';
            }
            if( rsp.features != null ) { 
                p.sections.feature_priorities.visible = 'yes';
                p.sections.feature_categories.visible = 'yes';
                p.data.feature_priorities = rsp.features.priorities;
                p.data.feature_categories = rsp.features.categories;
            } else {
                p.sections.feature_priorities.visible = 'no';
                p.sections.feature_categories.visible = 'no';
            }
            if( rsp.questions != null ) { 
                p.sections.question_priorities.visible = 'yes';
                p.sections.question_categories.visible = 'yes';
                p.data.question_priorities = rsp.questions.priorities;
                p.data.question_categories = rsp.questions.categories;
            } else {
                p.sections.question_priorities.visible = 'no';
                p.sections.question_categories.visible = 'no';
            }
            p.sections.unknown.visible = 'no';
            if( rsp.unknown != null ) {
                p.data.unknown = rsp.unknown;
                p.sections.unknown.visible = 'yes';
            }

            if( rsp.latest != null && rsp.latest.length > 0 ) {
                p.data.latest = rsp.latest;
                p.sections.latest.visible = 'yes';
            } else {
                p.data.latest = {};
                p.sections.latest.visible = 'no';
            }

            p.refresh();
            p.show(cb);
        });
    };

    this.showList = function(cb, type, category, priority) {
        if( type != null ) {
            if( type == 'bug_categories' || type == 'bug_priorities' ) {
                type = 1;
                this.list.title = 'Bugs';
            } else if( type == 'feature_categories' || type == 'feature_priorities' ) {
                type = 2;
                this.list.title = 'Features';
            } else if( type == 'question_categories' || type == 'question_priorities' ) {
                type = 3;
                this.list.title = 'Questions';
            } else {
                type = 0;
                this.list.sections.open.label = 'Other';
            }
            this.list.bug_type = type;
        }
        if( category != null || priority != null ) {
            this.list.category = category;
            switch (priority) {
                case 'Low': this.list.priority = 10; break;
                case 'Medium': this.list.priority = 30; break;
                case 'High': this.list.priority = 50; break;
                default: this.list.priority = null;
            }
            this.list.sections.open.label = decodeURIComponent(category);
            if( priority != null ) {
                this.list.sections.open.label = decodeURIComponent(priority);
            }
        }

        var args = {'tnid':M.curTenantID, 
            'type':this.list.bug_type, 
            'order':'openclosed',
            'status':'all',
            };
        if( this.list.category != null && this.list.priority != null ) {
            args.category = this.list.category;
            args.priority = this.list.priority;
        } else if( this.list.priority != null ) {
            args.priority = this.list.priority;
        } else if( this.list.category != null ) {
            args.category = this.list.category;
        }

        var r = M.api.getJSONCb('ciniki.bugs.bugList', 
            args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_bugs_main.list;
                p.data = {};
                if( rsp.open != null ) {
                    p.data.open = rsp.open;
                } 
                if( rsp.closed != null ) {
                    p.data.closed = rsp.closed;
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.showEdit = function(cb, bid, type, category) {
        if( bid != null ) { this.edit.bug_id = bid; }
        this.edit.reset();
        if( this.edit.bug_id > 0 ) {
            //
            // Setup the details for the question
            //
            this.edit.forms.bug.thread.visible = 'yes';
            this.edit.forms.bug._followup.label = 'Add your response';
            this.edit.forms.bug.info.visible = 'yes';
            this.edit.forms.feature.thread.visible = 'yes';
            this.edit.forms.feature._followup.label = 'Add your response';
            this.edit.forms.feature.info.visible = 'yes';
            this.edit.forms.question.thread.visible = 'yes';
            this.edit.forms.question._followup.label = 'Add your response';
            this.edit.forms.question.info.visible = 'yes';
            var r = M.api.getJSONCb('ciniki.bugs.bugGet', 
                {'tnid':M.curTenantID, 'bug_id':this.edit.bug_id}, function(r) {
                    if( r.stat != 'ok' ) {
                        M.api.err(r);
                        return false;
                    }
                    var p = M.ciniki_bugs_main.edit;
                    p.data = r.bug;
                    if( r.bug.type == 1 ) {
                        p.title = 'Bug';
                    } else if( r.bug.type == 2 ) {
                        p.title = 'Feature';
                    }
                    p.subject = '#' + p.bug_id + ' - ' + r.bug.subject;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.data = {'type':1, 'priority':10, 'status':1, 'options':3, 'subject':'', 'category':'', 'content':'', 'followers':[]};
            this.edit.forms.bug.thread.visible = 'no';
            this.edit.forms.bug._followup.label = 'Details';
            this.edit.forms.bug.info.visible = 'no';
            this.edit.forms.feature.thread.visible = 'no';
            this.edit.forms.feature._followup.label = 'Details';
            this.edit.forms.feature.info.visible = 'no';
            this.edit.forms.question.thread.visible = 'no';
            this.edit.forms.question._followup.label = 'Details';
            this.edit.forms.question.info.visible = 'no';
            if( type == 'question' ) {
                this.edit.data.type = 3;
            } else if( type == 'feature' ) {
                this.edit.data.type = 2;
            } else {
                this.edit.data.type = 1;
            }
            if( type != null ) {
                this.edit.data.type = type;
            }
            if( category != null && category != '' && category != 'Uncategorized' ) {
                this.edit.data.category = decodeURIComponent(category);
            }

            this.edit.refresh();
            this.edit.show(cb);
        }
    }

    this.saveBug = function() {
        if( this.edit.bug_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.bugs.bugUpdate', 
                    {'tnid':M.curTenantID, 'bug_id':this.edit.bug_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_bugs_main.edit.close();
                    });
            } else {
                M.ciniki_bugs_main.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            var rsp = M.api.postJSONCb('ciniki.bugs.bugAdd', 
                {'tnid':M.curTenantID, 'state':'Open'}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_bugs_main.edit.close();
                });
        }
    };

    this.searchBugs = function(cb, needle) {
        if( needle != null ) {
            this.search.needle = needle;
        }
        var r = M.api.getJSONCb('ciniki.bugs.bugSearchFull', 
            {'tnid':M.curTenantID, 'start_needle':this.search.needle, 'status':'1'}, function(rsp) {
                if( r.stat != 'ok' ) {
                    M.api.err(r);
                    return false;
                }
                M.ciniki_bugs_main.search.data = r;
                M.ciniki_bugs_main.search.refresh();
                M.ciniki_bugs_main.search.show(cb);
            });
    };
}
