<?php
//
// Description
// -----------
// This method will return the binary image data for a users avatar.
//
// Info
// ----
// publish:			yes
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:				The ID of the user to get the avatar for.
// version:				The version of the image (regular, thumbnail)
//
//						*note* the thumbnail is not referring to the size, but to a 
//						square cropped version, designed for use as a thumbnail.
//						This allows only a portion of the original image to be used
//						for thumbnails, as some images are too complex for thumbnails.
//
// maxlength:			The max length of the longest side should be.  This allows
//						for generation of thumbnail's, etc.
//
// Returns
// -------
// Binary image data
//
function ciniki_users_avatarGet($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		// FIXME: Add ability to get another avatar for sysadmin
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 	
		'version'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Version'),
		'maxlength'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Maximum Length'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.avatarGet', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the users avatar image_id
	//
	$avatar_id = 0;
	if( isset($ciniki['session']['user']['avatar_id']) && $ciniki['session']['user']['avatar_id'] > 0 ) {
		$avatar_id = $ciniki['session']['user']['avatar_id'];
	}

	//
	// FIXME: If no avatar specified, return the default icon
	//

	ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'getUserImage');
	return ciniki_images_getUserImage($ciniki, $ciniki['session']['user']['id'], $avatar_id, $args['version'], $args['maxlength']);
}
?>
