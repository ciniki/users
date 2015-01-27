//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_users_prefs() {
	this.prefs = null;
	this.time_format_options = {
		'%l:%i %p':'1:00 pm',
		'%H:%i':'13:00',
		// '%b %e, %Y %H:%i':'Jan 1, 2011 00:01',
		// '%Y/%m/%d %l:%i %p':'2010/12/31 1:00 pm',
		// '%Y/%m/%d %H:%i':'2010/12/31 00:01',
		// '%Y-%m-%d %l:%i %p':'2010-12-31 1:00 pm',
		};
	this.datetime_format_options = {
		'%b %e, %Y %l:%i %p':'Jan 1, 2011 1:00 pm',
		'%a %b %e, %Y %l:%i %p':'Mon Jan 1, 2011 1:00 pm',
		// '%b %e, %Y %H:%i':'Jan 1, 2011 00:01',
		// '%Y/%m/%d %l:%i %p':'2010/12/31 1:00 pm',
		// '%Y/%m/%d %H:%i':'2010/12/31 00:01',
		// '%Y-%m-%d %l:%i %p':'2010-12-31 1:00 pm',
		'%Y-%m-%d %H:%i':'2010-12-31 00:01',
		};

	this.date_format_options = {
		// '%b %e %Y':'Jan 1 2011',
		'%a %b %e, %Y':'Mon Jan 1, 2011',
		'%b %e, %Y':'Jan 1, 2011',
		// '%M %e, %Y':'January 1, 2011',
		// '%Y/%m/%d':'2010/12/31',
		'%Y-%m-%d':'2010-12-31',
		};
	
	this.history_date_options = {
		'age':'10 days ago',
		'datetime':'Sep 9, 2012 8:40am',
		'datetimeage':'Sep 9, 2012 8:40am (10 days ago)',
	};
	this.toggleOptions = {
		'no':'No',
		'yes':'Yes',
	};

	this.init = function() {
		this.prefs = new M.panel('My Preferences',
			'ciniki_users_prefs', 'prefs',
			'mc', 'narrow', 'sectioned', 'ciniki.users.prefs');
		this.prefs.sections = {
			'_ui':{'label':'Interface Preferences', 'fields':{
				'ui-history-date-display':{'label':'History Date', 'type':'select', 'options':this.history_date_options},
				'ui-mode-guided':{'label':'Guided Mode', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				}},
			'':{'label':'Preferences', 'fields':{
				'settings.time_format':{'label':'Time', 'type':'select', 'options':this.time_format_options},
				'settings.date_format':{'label':'Date', 'type':'select', 'options':this.date_format_options},
				'settings.datetime_format':{'label':'Date and Time', 'type':'select', 'options':this.datetime_format_options},
				}},
			};
		this.prefs.fieldValue = function(s, i, d) { return this.data[i]; }
		this.prefs.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.users.getDetailHistory', 'args':{'user_id':M.userID, 'field':i}};
		}	
		this.prefs.addButton('save', 'Save', 'M.ciniki_users_prefs.save();');
		this.prefs.addClose('Cancel');
	}

	this.start = function(cb, appPrefix) {
		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_users_prefs', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
		
		// Get the detail for the users preferences.  
		var rsp = M.api.getJSONCb('ciniki.users.getDetails', 
			{'user_id':M.userID, 'keys':'ui,settings'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_users_prefs.prefs.data = rsp.details;
				M.ciniki_users_prefs.prefs.show(cb);
			});
	}

	// 
	// Submit the form
	//
	this.save = function() {
		// Serialize the form data into a string for posting
		var c = this.prefs.serializeForm('no');
		if( c != '' ) {
			M.api.postJSONCb('ciniki.users.updateDetails', {'user_id':M.userID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_users_prefs.prefs.close();
			});
		} else {
			this.prefs.close();
		}
	}
}
