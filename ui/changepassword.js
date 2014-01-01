//
// This class is use to display the change password form and send the form info to the cinikiAPI
//
function ciniki_users_changepassword() {
	this.chgpwd = null;

	this.init = function() {}

	this.start = function(cb, appPrefix) {
		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_users_changepassword', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.chgpwd = new M.panel('Change Password',
			'ciniki_users_changepassword', 'chgpwd',
			appPrefix, 'narrow', 'sectioned', 'ciniki.users.changepassword');
		this.chgpwd.data = null;

		this.chgpwd.sections = { 
			'_oldpassword':{'label':'Current', 'fields':{
				'oldpassword':{'label':'Old Password', 'hidelabel':'yes', 'hint':'Old Password', 'type':'password'},
				}},
			'_newpassword':{'label':'New Password', 'fields':{
				'newpassword1':{'label':'New Password', 'hidelabel':'yes', 'hint':'New Password', 'type':'password'},
				'newpassword2':{'label':'Re-type New Password', 'hidelabel':'yes', 'hint':'Re-type New Password', 'type':'password'}
				}},
			}

		this.chgpwd.fieldValue = function(s, i, d) { return ''; }
		this.chgpwd.addButton('save', 'Save', 'M.ciniki_users_changepassword.save();');
		this.chgpwd.addClose('Cancel');

		this.chgpwd.show(cb);
	}

	// 
	// Submit the form
	//
	this.save = function() {
		var oldpassword = encodeURIComponent(document.getElementById(this.chgpwd.panelUID + '_oldpassword').value);
		var newpassword1 = encodeURIComponent(document.getElementById(this.chgpwd.panelUID + '_newpassword1').value);
		var newpassword2 = encodeURIComponent(document.getElementById(this.chgpwd.panelUID + '_newpassword2').value);

		if( newpassword1 != newpassword2 ) {
			alert("The password's do not match.  Please enter them again");
			return false;
		}
		if( newpassword1.length < 8 ) {
			alert("Passwords must be at least 8 characters long");
			return false;
		}
		// Make sure the password is not included in the URL, where it will be saved in log files
		var c = 'oldpassword=' + oldpassword + '&newpassword=' + newpassword1;
		M.api.postJSONCb('ciniki.users.changePassword', {}, c, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			alert("Your password was changed, you must now re-login.");
			M.logout();
		});
	}
}
