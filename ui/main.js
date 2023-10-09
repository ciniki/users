//
// This class will display the form to allow admins and tenant owners to 
// change the details of their tenant
//
function ciniki_users_main() {
    //
    // The main account panel
    //
    this.menu = new M.panel('My Account', 'ciniki_users_main', 'menu', 'mc', 'narrow', 'sectioned', 'ciniki.users.main.menu');
    this.menu.sections = {
        '_':{'label':'', 'list':{
            'myinfo':{'label':'My Information', 'fn':'M.ciniki_users_main.info.open(\'M.ciniki_users_main.menu.show();\');'},
            'prefs':{'label':'Preferences', 'fn':'M.ciniki_users_main.prefs.open(\'M.ciniki_users_main.menu.show();\');'},
            'avatar':{'label':'Avatar', 'fn':'M.ciniki_users_main.avatar.open(\'M.ciniki_users_main.menu.show();\');'},
            'password':{'label':'Change Password', 'fn':'M.ciniki_users_main.chgpwd.open(\'M.ciniki_users_main.menu.show();\');'},
        }},
    }
    this.menu.addClose('Back');

    //
    // The account settings
    //
    this.info = new M.panel('My Info', 'ciniki_users_main', 'info', 'mc', 'narrow', 'sectioned', 'ciniki.users.main.info');
    this.info.sections = {
        'name':{'label':'Name', 'fields':{
            'user.firstname':{'label':'First', 'type':'text'},
            'user.lastname':{'label':'Last', 'type':'text'},
            'user.display_name':{'label':'Display', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_users_main.info.save();'},
            }},
    };
    this.info.fieldValue = function(s, i, d) { return this.data[i]; }
    this.info.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.users.getDetailHistory', 'args':{'user_id':M.userID, 'field':i}};
    }
    this.info.open = function(cb) {
        M.api.getJSONCb('ciniki.users.getDetails', {'user_id':M.userID, 'keys':'user'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_users_main.info;
            p.data = rsp.details;
            p.show(cb);
        });
    }
    this.info.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.users.updateDetails', {'user_id':M.userID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_users_main.info.close();
            });
        }
        this.close();
    }
    this.info.addButton('save', 'Save', 'M.ciniki_users_main.info.save();');
    this.info.addClose('Cancel');

    //
    // The user preferences panel
    //
    this.prefs = new M.panel('My Preferences', 'ciniki_users_main', 'prefs', 'mc', 'medium', 'sectioned', 'ciniki.users.main.prefs');
    this.prefs.sections = {
        '_ui':{'label':'Interface Preferences', 'fields':{
            'ui-history-date-display':{'label':'History Date', 'type':'select', 'options':{
                'age':'10 days ago',
                'datetime':'Sep 9, 2012 8:40am',
                'datetimeage':'Sep 9, 2012 8:40am (10 days ago)',
                }},
            }},
        '_calendar':{'label':'Calendar Options', 
            'visible':function() { return M.modOn('ciniki.calendars') ? 'yes' : 'no'; },
            'fields':{
                'ui-calendar-view':{'label':'Default View', 'type':'toggle', 'default':'mw', 'toggles':{'day':'Day', 'mw':'Month'}},
                'ui-calendar-remember-date':{'label':'Remember Date', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
            }},
        '_prefs':{'label':'Preferences', 'fields':{
            'settings.time_format':{'label':'Time', 'type':'select', 'options':{
                '%l:%i %p':'1:00 pm',
                '%H:%i':'13:00',
                }},
            'settings.date_format':{'label':'Date', 'type':'select', 'options':{
                '%a %b %e, %Y':'Mon Jan 1, 2011',
                '%b %e, %Y':'Jan 1, 2011',
                '%Y-%m-%d':'2010-12-31',
                }},
            'settings.datetime_format':{'label':'Date and Time', 'type':'select', 'options':{
                '%b %e, %Y %l:%i %p':'Jan 1, 2011 1:00 pm',
                '%a %b %e, %Y %l:%i %p':'Mon Jan 1, 2011 1:00 pm',
                '%Y-%m-%d %H:%i':'2010-12-31 00:01',
                }},
            'settings.temperature_units':{'label':'Temperature', 'type':'select', 'options':{
                'celsius':'Celsius',
                'fahrenheit':'Fahrenheit',
                }},
            'settings.windspeed_units':{'label':'Wind Speed', 'type':'select', 'options':{
                'kph':'Kilometers/Hour',
//                'mps':'Meters/Second',
                'mph':'Miles/Hour',
                }},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_users_main.prefs.save();'},
            }},
    };
    this.prefs.fieldValue = function(s, i, d) { return this.data[i]; }
    this.prefs.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.users.getDetailHistory', 'args':{'user_id':M.userID, 'field':i}};
    }   
    this.prefs.open = function(cb) {
        M.api.getJSONCb('ciniki.users.getDetails', {'user_id':M.userID, 'keys':'ui,settings'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_users_main.prefs;
            p.data = rsp.details;
            p.sections._calendar.fields['ui-calendar-view'].active = (rsp.details['ui-calendar-view'] != null?'yes':'no');
            p.sections._calendar.fields['ui-calendar-remember-date'].active = (rsp.details['ui-calendar-remember-date'] != null?'yes':'no');
            p.refresh();
            p.show(cb);
        });
    }
    this.prefs.save = function() {
        var c = this.serializeForm('no');
        if( this.formValue('settings.temperature_units') != this.fieldValue('settings.temperature_units') ) {
            M.userSettings['ui-temperature-units'] = this.formValue('settings.temperature_units');
            if( M.userSettings['ui-temperature-units'] == 'fahrenheit' ) {
                M.userSettings['ui-temperature-unit'] = 'F';
            } else {
                M.userSettings['ui-temperature-unit'] = 'C';
            }
        }
        if( this.formValue('settings.windspeed_units') != this.fieldValue('settings.windspeed_units') ) {
            M.userSettings['ui-windspeed-units'] = this.formValue('settings.windspeed_units');
        }
        if( c != '' ) {
            M.api.postJSONCb('ciniki.users.updateDetails', {'user_id':M.userID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_users_main.prefs.close();
            });
        } else {
            this.close();
        }
    }
    this.prefs.addButton('save', 'Save', 'M.ciniki_users_main.prefs.save();');
    this.prefs.addClose('Cancel');

    //
    // The avatar panel
    //
    this.avatar = new M.panel('My Avatar', 'ciniki_users_main', 'avatar', 'mc', 'narrow', 'sectioned', 'ciniki.users.main.avatar');
    this.avatar.data = {'image_id':0};
    this.avatar.sections = {
        '_image':{'label':'Upload Photo', 'type':'imageform', 'fields':{
            'image_id':{'label':'', 'hidelabel':'yes', 'controls':'all', 'type':'image_id'},
            }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_users_main.avatar.save();'},
            }},
    };
    this.avatar.imageURL = function(s, i, field, img_id, nM) {
        return M.api.getBinaryURL('ciniki.images.get', {'tnid':0, 'image_id':img_id, 'version':'original', 'maxwidth':'0', 'maxheight':'600'});
    }
    this.avatar.fieldValue = function(s, i, d) { return this.data[i]; }
    this.avatar.addDropImageAPI = 'ciniki.images.addUserImage';
    this.avatar.addDropImage = function(iid) {
        this.setFieldValue('image_id', iid);
        return true;
    };
    this.avatar.deleteImage = function(fid) {
        this.setFieldValue('image_id', 0);
        return true;
    };
    this.avatar.open = function(cb) {
    // Why was this line here? Removed Jun 20, 2020.
    // Causes major bug when they try to do anything after
//        M.curTenantID = 0;
        M.api.getJSONCb('ciniki.users.get', {'user_id':M.userID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_users_main.avatar;
            p.data.image_id = rsp.user.avatar_id;
            p.refresh();
            p.show(cb);
        });
    }
    this.avatar.save = function() {
        var c = this.serializeForm('no');  
        if( c != '' ) {
            M.api.postJSONCb('ciniki.users.avatarSave', {'user_id':M.userID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.avatarID = rsp.avatar_id;
                M.loadAvatar();
                M.ciniki_users_main.avatar.close();
            });
        } else {
            M.ciniki_users_main.avatar.close();
        }
    }
    this.avatar.addClose('Cancel');

    //
    // The change password panel
    //
    this.chgpwd = new M.panel('Change Password', 'ciniki_users_main', 'chgpwd', 'mc', 'narrow', 'sectioned', 'ciniki.users.main.chgpwd');
    this.chgpwd.data = null;
    this.chgpwd.sections = { 
        '_oldpassword':{'label':'Current', 'fields':{
            'oldpassword':{'label':'Old Password', 'hidelabel':'yes', 'hint':'Old Password', 'type':'password'},
            }},
        '_newpassword':{'label':'New Password', 'fields':{
            'newpassword1':{'label':'New Password', 'hidelabel':'yes', 'hint':'New Password', 'type':'password'},
            'newpassword2':{'label':'Re-type New Password', 'hidelabel':'yes', 'hint':'Re-type New Password', 'type':'password'}
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_users_main.chgpwd.save();'},
            }},
        }
    this.chgpwd.open = function(cb) {
        this.show(cb);
    }
    this.chgpwd.fieldValue = function(s, i, d) { return ''; }
    this.chgpwd.save = function() {
        var oldpassword = encodeURIComponent(document.getElementById(this.panelUID + '_oldpassword').value);
        var newpassword1 = encodeURIComponent(document.getElementById(this.panelUID + '_newpassword1').value);
        var newpassword2 = encodeURIComponent(document.getElementById(this.panelUID + '_newpassword2').value);

        if( newpassword1 != newpassword2 ) {
            M.alert("The password's do not match.  Please enter them again");
            return false;
        }
        if( newpassword1.length < 8 ) {
            M.alert("Passwords must be at least 8 characters long");
            return false;
        }
        // Make sure the password is not included in the URL, where it will be saved in log files
        var c = 'oldpassword=' + oldpassword + '&newpassword=' + newpassword1;
        M.api.postJSONCb('ciniki.users.changePassword', {}, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.alert("Your password was changed, you must now re-login.");
            M.logout();
        });
    }
    this.chgpwd.addButton('save', 'Save', 'M.ciniki_users_main.chgpwd.save();');
    this.chgpwd.addClose('Cancel');

    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer('mc', 'ciniki_users_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.panel != null && args.panel == 'prefs' ) {
            this.prefs.open(cb);
        } else {
            this.menu.show(cb);
        }
    }
}
