<?php
namespace SIM\USERMANAGEMENT;
use SIM;

const MODULE_VERSION		= '8.2.4';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

add_filter('sim_submenu_usermanagement_description', __NAMESPACE__.'\moduleDescription', 10, 2);
function moduleDescription($description, $moduleSlug){
	ob_start();
	$links		= [];
	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'account_page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Account</a><br>";
	}

	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'user_edit_page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Edit users</a><br>";
	}

	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'account_create_page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Create user accounts</a><br>";
	}

	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'pending_users_page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Pending user accounts</a><br>";
	}

	if(!empty($links)){
		?>
		<p>
			<strong>Auto created pages:</strong><br>
			<?php
			foreach($links as $link){
				echo $link;
			}
			?>
		</p>
		<?php
	}

	return $description.ob_get_clean();
}

add_filter('sim_submenu_usermanagement_options', __NAMESPACE__.'\moduleOptions', 10, 2);
function moduleOptions($optionsHtml, $settings){
	ob_start();
	?>
	<label>
		<input type='checkbox' name='tempuser' value='tempuser' <?php if(isset($settings['tempuser'])){echo 'checked';}?>>
		Enable temporary user accounts
	</label>
	<br>
	<label for="check-details-mail-freq">How often should people asked to check their details for changes?</label>
	<br>
	<select name="check-details-mail-freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['check-details-mail-freq']);
		?>
	</select>
	<br>
	<br>
	<label>Select any forms you want to be available on the account page</label>
	<br>
	<?php

	foreach(['family', 'generic', 'location', 'profile picture', 'security'] as $form){
		if(is_array($settings['enabled-forms']) && in_array($form, $settings['enabled-forms'])){
			$checked	= 'checked';
		}else{
			$checked	= '';
		}

		echo "<label>";
			echo "<input type='checkbox' name='enabled-forms[]' value='$form' $checked>";
			echo ucfirst($form);
		echo "</label><br>";
	}

	return ob_get_clean().$optionsHtml;
}

add_filter('sim_email_usermanagement_settings', __NAMESPACE__.'\emailSettings', 10, 2);
function emailSettings($html, $settings){
	ob_start();
	?>
	<h4>E-mail to people who's account is just approved</h4>
	<label>Define the e-mail people get when they are added to the website</label>
	<?php
	$accountApproveddMail    = new AccountApproveddMail(wp_get_current_user());
	$accountApproveddMail->printPlaceholders();
	$accountApproveddMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail to people who's account is just created</h4>
	<label>Define the e-mail people get when they are added to the website</label>
	<?php
	$accountCreatedMail    = new AccountCreatedMail(wp_get_current_user());
	$accountCreatedMail->printPlaceholders();
	$accountCreatedMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail to people who's account is about to expire</h4>
	<label>Define the e-mail people get when they are about to be removed from the website</label>
	<?php
	$accountExpiryMail    = new AccountExpiryMail(wp_get_current_user());
	$accountExpiryMail->printPlaceholders();
	$accountExpiryMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail to people who's account is deleted</h4>
	<label>Define the e-mail people get when they are removed from the website</label>
	<?php
	$accountRemoveMail    = new AccountRemoveMail(wp_get_current_user());
	$accountRemoveMail->printPlaceholders();
	$accountRemoveMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail to people who have not logged in for more than a year</h4>
	<label>Define the e-mail people get when they have not logged into the website for more than a year</label>
	<?php
	$weMissYouMail    = new WeMissYouMail(wp_get_current_user());
	$weMissYouMail->printPlaceholders();
	$weMissYouMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail to people who's vaccinations are about to expire</h4>
	<label>Define the e-mail people get when one or more vaccinations are about to expire</label>
	<?php
	$vaccinationWarningMail    = new AdultVaccinationWarningMail(wp_get_current_user());
	$vaccinationWarningMail->printPlaceholders();
	$vaccinationWarningMail->printInputs($settings);
    ?>
	<br>
	<br>

	<h4>E-mail to parents who's children have vaccinations who are about to expire</h4>
	<label>Define the e-mail people get when one or more vaccinations of a child are about to expire</label>
	<?php
	$vaccinationWarningMail    = new ChildVaccinationWarningMail(wp_get_current_user());
	$vaccinationWarningMail->printPlaceholders();
	$vaccinationWarningMail->printInputs($settings);

	return $html.ob_get_clean();
}

add_filter('sim_module_usermanagement_after_save', __NAMESPACE__.'\moduleUpdated', 10, 2);
function moduleUpdated($options, $oldOptions){	
	// image sub size for profile pictures
	if(!function_exists('wp_generate_attachment_metadata')){
		require_once(ABSPATH.'/wp-admin/includes/image.php');
	}
	wp_generate_attachment_metadata($postId, "$newPath/$filename");


	// Create account page
	$options	= SIM\ADMIN\createDefaultPage($options, 'account_page', 'Account', '[user-info currentuser=true]', $oldOptions);

	// Create user edit page
	$options	= SIM\ADMIN\createDefaultPage($options, 'user_edit_page', 'Edit users', '[user-info]', $oldOptions);

	// Create user create page
	$options	= SIM\ADMIN\createDefaultPage($options, 'account_create_page', 'Add user account', '[create_user_account]', $oldOptions);

	// Create pending users page
	$options	= SIM\ADMIN\createDefaultPage($options, 'pending_users_page', 'Pending user accounts', '[pending_user]', $oldOptions);
	
	scheduleTasks();

	return $options;
}

add_filter('display_post_states', __NAMESPACE__.'\postStates', 10, 2);
function postStates( $states, $post ) {

	if ( in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'account_page', false))) {
		$states[] = __('Account page');
	}elseif(in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'user_edit_page', false)) ) {
		$states[] = __('User edit page');
	}elseif(in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'account_create_page', false))) {
		$states[] = __('Account create page');
	}elseif(in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'pending_users_page', false))) {
		$states[] = __('Pending users page');
	}

	return $states;
}

add_action('sim_module_usermanagement_deactivated', __NAMESPACE__.'\moduleDeActivated');
function moduleDeActivated($options){
	$removePages	= [];
	
	if(is_array($options['account_page'])){
		$removePages	= array_merge($removePages, $options['2fa_page']);
	}

	if(is_array($options['user_edit_page'])){
		$removePages	= array_merge($removePages, $options['user_edit_page']);
	}

	if(is_array($options['account_create_page'])){
		$removePages	= array_merge($removePages, $options['account_create_page']);
	}

	if(is_array($options['pending_users_page'])){
		$removePages	= array_merge($removePages, $options['pending_users_page']);
	}

	// Remove the auto created pages
	foreach($removePages as $page){
		// Remove the auto created page
		wp_delete_post($page, true);
	}

	wp_clear_scheduled_hook( 'birthday_check_action' );
	wp_clear_scheduled_hook( 'account_expiry_check_action' );
	wp_clear_scheduled_hook( 'vaccination_reminder_action' );
	wp_clear_scheduled_hook( 'check-details-mail_action' );
	wp_clear_scheduled_hook( 'check_last_login_date_action' );
}

add_action('sim_module_usermanagement_activated', __NAMESPACE__.'\moduleActivated');
function moduleActivated(){
	// Enable forms module
	if(!SIM\getModuleOption('forms', 'enable')){
		SIM\ADMIN\enableModule('forms');
	}

	// Import the forms
	$formBuilder	= new SIM\FORMS\FormBuilderForm();

	$files = glob(MODULE_PATH  . "imports/*.sform");
	foreach ($files as $file) {
		$formBuilder->importForm($file);
	}

	// add the last logindate for existing users
    foreach(get_users(['meta_key' => 'last_login_date','meta_compare'  => 'NOT EXISTS']) as $user){
        update_user_meta( $user->ID, 'last_login_date', date('Y-m-d'));
    }
}