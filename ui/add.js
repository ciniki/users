//
// This function allows users to be added to the database.  This will be called
// for adding sys admins and business owners.
//
function ciniki_users_add() {
	this.init = function() {
		this.add = new M.panel('Add Owner',
			'ciniki_users_add', 'add', 
			'mc', 'medium', 'sectioned', 'ciniki.users.add');
		this.add.default_data = {};
		this.add.data = {};
		this.add.sections = {	
			'email':{'label':'Email Address', 'fields':{
				'email.address':{'hidelabel':'yes', 'type':'email', 'livesearch':'no'},
				}},
			'username':{'label':'Username', 'fields':{
				'user.username':{'hidelabel':'yes', 'type':'text', 'livesearch':'no'}, 
				}},
			'name':{'label':'Contact', 'fields':{
				'user.firstname':{'label':'First', 'type':'text'},
				'user.lastname':{'label':'Last', 'type':'text'},
				'user.display_name':{'label':'Display', 'type':'text'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_users_add.save();'},
			}},
			};
		this.add.liveSearchCb = function(s, i, value) {
			if( i == 'user.username' ) {
				M.api.getJSONBgCb('ciniki.users.searchUsername', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_users_add.add.liveSearchShow(s, i, M.gE(M.ciniki_users_add.add.panelUID + '_' + i), rsp.users); 
					});
				return true;
			} else if( i == 'email.address' ) {
				M.api.getJSONBgCb('ciniki.users.searchEmail', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_users_add.add.liveSearchShow(s, i, M.gE(M.ciniki_users_add.add.panelUID + '_' + i), rsp.users); 
					});
				return true;
			}
		};
		this.add.liveSearchResultValue = function(s, f, i, j, d) { 
			switch(f) {
				case 'email.address': return d.user.email;
				case 'user.username': return d.user.username;
			}
			return '';
		}
		this.add.liveSearchResultRowFn = function(s, f, i, j, d) { return 'M.ciniki_users_add.add.close({\'id\':' + d.user.id + '});'}

		this.add.fieldValue = function(s, i, d) { return ''; }
		this.add.addButton('add', 'Add', 'M.ciniki_users_add.save();');
		this.add.addClose('Cancel');
	}

	this.start = function(cb, appPrefix) {
		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_users_add', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		if( (M.userPerms&0x01) == 1 ) {
			this.add.sections.email.fields['email.address'].livesearch = 'yes';
			this.add.sections.username.fields['user.username'].livesearch = 'yes';
		}
	
		this.add.reset();
		this.add.show(cb);
	}

	// 
	// Submit the form
	//
	this.save = function() {
		if( this.add.formValue('email.address') == '' ) {
			alert("You must specify a email address.");
			return false;
		}
		if( this.add.formValue('user.firstname') == '' ) {
			alert("You must specify a first name.");
			return false;
		}

		// Serialize the form data into a string for posting
		var c = this.add.serializeForm('yes');
		var rsp = M.api.postJSONCb('ciniki.users.add', 
			{'business_id':M.curBusinessID, 'welcome_email':'yes'}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_users_add.add.close({'id':rsp.id});
			});
	}
}

