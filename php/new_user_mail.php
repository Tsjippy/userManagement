<?php
namespace TSJIPPY\USERMANAGEMENT;
use TSJIPPY;

//Add link to the user menu to resend the confirmation e-mail
add_filter( 'user_row_actions', __NAMESPACE__.'\userRowActions', 10, 2 );
/**
 * Filter the actions displayed for each user in the users list table.
 *
 * @param array $actions The actions to display.
 * @param \WP_User $user The user object.
 * 
 * @return array The modified actions.
 */
function userRowActions( $actions, $user ) {
    $actions['Resend welcome mail'] = "<a href='".SITEURL."/wp-admin/users.php?send_activation_email=$user->ID'>Resend welcome email</a>";
    return $actions;
}

add_action('admin_menu', __NAMESPACE__.'\adminMenu');
function adminMenu() {
	//Process the request
	if(!empty($_GET['send_activation_email']) && is_numeric($_GET['send_activation_email'] )){
		$userId    = $_GET['send_activation_email'];
		$email = get_userdata($userId )->user_email;
		TSJIPPY\printArray("Sending welcome email to $email");
		wp_new_user_notification($userId, null, 'user');
	}
}

//Apply our e-mail settings
add_filter( 'wp_new_user_notification_email', __NAMESPACE__.'\notificationEmail', 10, 2);
/**
 * Filter the e-mail sent to the user when a new account is created or when the password is reset.
 * 
 * @param array $args {
 * 	@type string $to The email address(es) to send the email to.
 * 	@type string $subject The subject of the email.
 * 	@type string $message The message body of the email.
 * 	@type string $headers The headers of the email.
 * }
 * @param \WP_User $user The user object for the user being notified.
 * 
 * @return array The modified email arguments.
 */
function notificationEmail($args, $user){
	
	$expirationDuration	= apply_filters( 'password_reset_expiration', DAY_IN_SECONDS );
	$key		 		= get_password_reset_key($user);
	if(is_wp_error($key)){
		return $key;
	}
	$validTill			= time()+$expirationDuration;

	$format				= get_option('date_format').' '.get_option('time_format');

	$validTillString	= gmdate($format, $validTill);

	$url = add_query_arg([
		'key' 	=> $key,
		'login' => $user->user_login
	], wp_lostpassword_url());

	if(get_user_meta($user->ID, 'disabled', true) == 'pending'){
		$mail = new AccountApproveddMail($user, $url, $validTillString);
		$mail->filterMail();
	}else{
		$mail = new AccountCreatedMail($user, $url, $validTillString);
		$mail->filterMail();
	}

	$args['subject']	= $mail->subject;
	$args['message']	= $mail->message;

	return $args;
}