//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_users_main() {
	this.menu = null;

	this.init = function() {
		this.menu = new M.panel('My Account',
			'ciniki_users_main', 'menu', 
			'mc', 'narrow', 'sectioned', 'ciniki.users.main.menu');
		this.menu.sections = {'_':{'label':'', 'list':{
			'myinfo':{'label':'My Information', 'fn':'M.startApp(\'ciniki.users.settings\', null, \'M.ciniki_users_main.menu.show();\');'},
			'prefs':{'label':'Preferences', 'fn':'M.startApp(\'ciniki.users.prefs\', null, \'M.ciniki_users_main.menu.show();\');'},
			'avatar':{'label':'Avatar', 'fn':'M.startApp(\'ciniki.users.avatar\', null, \'M.ciniki_users_main.menu.show();\');'},
			'password':{'label':'Change Password', 'fn':'M.startApp(\'ciniki.users.changepassword\', null, \'M.ciniki_users_main.menu.show();\');'},
		}}};
		this.menu.addClose('Back');
	}

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
			alert('App Error');
			return false;
		} 

		this.menu.show(cb);
	}
}

