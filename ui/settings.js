//
//
function ciniki_users_settings() {
	//
	// The panel
	//
	this.settings = null;

	this.init = function() {
		this.settings = new M.panel('My Info',
			'ciniki_users_settings', 'settings',
			'mc', 'narrow', 'sectioned', 'ciniki.users.settings');
		this.settings.sections = {
			'name':{'label':'Name', 'fields':{
				'user.firstname':{'label':'First', 'type':'text'},
				'user.lastname':{'label':'Last', 'type':'text'},
				'user.display_name':{'label':'Display', 'type':'text'},
				}},
		};
		this.settings.fieldValue = function(s, i, d) { return this.data[i]; }
		this.settings.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.users.getDetailHistory', 'args':{'user_id':M.userID, 'field':i}};
		}
		this.settings.addButton('save', 'Save', 'M.ciniki_users_settings.save();');
		this.settings.addClose('Cancel');
	}

	this.start = function(cb, appPrefix) {
		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_users_settings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		// Get the details for the user.  
		M.api.getJSONCb('ciniki.users.getDetails', {'user_id':M.userID, 'keys':'user'}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				alert("Error: #" + rsp.err.code + ' - ' + rsp.err.msg);
				return false;
			}
			var p = M.ciniki_users_settings.settings;
			p.data = rsp.details;
			p.show(cb);
		});
	}

	// 
	// Submit the form
	//
	this.save = function() {
		// Serialize the form data into a string for posting
		var c = this.settings.serializeForm('no');
		if( c != '' ) {
			M.api.postJSONCb('ciniki.users.updateDetails', {'user_id':M.userID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_users_settings.settings.close();
			});
		}
		this.settings.close();
	}
}

