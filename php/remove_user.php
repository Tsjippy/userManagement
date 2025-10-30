<?php
namespace SIM\USERMANAGEMENT;
use SIM;

// Send message about deletion
add_action('delete_user', __NAMESPACE__.'\userDeleted');
function userDeleted($userId){
	$userdata		= get_userdata($userId);
	$displayname	= $userdata->display_name;

	$attachmentId = get_user_meta($userId, 'profile_picture', true);
	if(is_numeric($attachmentId)){
		//Remove profile picture
		wp_delete_attachment($attachmentId, true);
		SIM\printArray("Removed profile picture for user $displayname");
	}

	//Send e-mail
	$accountRemoveMail    = new AccountRemoveMail($userdata);
	$accountRemoveMail->filterMail();
						
	wp_mail( $userdata->user_email, $accountRemoveMail->subject, $accountRemoveMail->message);
}