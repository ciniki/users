//
//
function ciniki_users_avatar() {
	//
	// The panel
	//
	this.settings = null;

	this.init = function() {
		this.edit = new M.panel('My Avatar',
			'ciniki_users_avatar', 'edit',
			'mc', 'narrow', 'sectioned', 'ciniki.users.avatar.edit');
		this.edit.data = {'image_id':0};
		this.edit.sections = {
			'_image':{'label':'Upload Photo', 'type':'imageform', 'fields':{
				'image_id':{'label':'', 'hidelabel':'yes', 'controls':'all', 'type':'image_id'},
				}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_users_avatar.savePhoto();'},
				}},
		};
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.addDropImageAPI = 'ciniki.images.addUserImage';
		this.edit.addDropImage = function(iid) {
			this.setFieldValue('image_id', iid);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue('image_id', 0);
			return true;
		};
		this.edit.addClose('Cancel');
	}

	this.start = function(cb, appPrefix) {
		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_users_avatar', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		// Get the detail for the users preferences.  
		var rsp = M.api.getJSONCb('ciniki.users.get', {'user_id':M.userID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_users_avatar.edit;
			p.data.image_id = rsp.user.avatar_id;
			p.refresh();
			p.show(cb);
		});
	}

	// 
	// Submit the form
	//
	this.savePhoto = function() {
		var c = this.edit.serializeForm('no');	
		if( c != '' ) {
			var rsp = M.api.postJSONCb('ciniki.users.avatarSave', {'user_id':M.userID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.avatarID = rsp.avatar_id;
				M.loadAvatar();
				M.ciniki_users_avatar.edit.close();
			});
		} else {
			M.ciniki_users_avatar.edit.close();
		}
	}
}

