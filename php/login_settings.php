<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//check if account is disabled
add_filter( 'wp_authenticate_user', __NAMESPACE__.'\authenticateUser', 10, 2);
function authenticateUser($user, $password){
	if (
		!is_wp_error( $user ) && 									// there is no error
		get_user_meta( $user->ID, 'disabled', true ) && 			// the account is disabled
		get_bloginfo( 'admin_email' ) !== $user->user_email			// this is not the main admin
	) {
		$user = new \WP_Error(
			'account_disabled_error',
			'Your account has been disabled for safety reasons.<br>Contact the office if you want to have it enabled again.'
		);
	}

	return $user;
}

function changePasswordForm($userId = null){
	if(is_numeric($userId)){
		$user		= get_userdata($userId);
	}else{
		$user		= wp_get_current_user();
	}
	$name		= $user->display_name;
	$disabled	= get_user_meta( $user->ID, 'disabled', true );
	if($disabled){
		$actionText	= 'enable';
	}else{
		$actionText	= 'disable';
	}
	
	ob_start();
	
	//Check if action is needed
	if(
		isset($_REQUEST['action']) 			&&
		isset($_REQUEST['wp-2fa-nonce'])	&&
		isset($_REQUEST['user-id'])			&&
		wp_verify_nonce( $_REQUEST['wp-2fa-nonce'], "wp-2fa-reset-nonce_".$_REQUEST['user-id'])
	){
		if($_REQUEST['action'] == 'Reset 2FA' && function_exists('SIM\LOGIN\reset2fa')){
			SIM\LOGIN\reset2fa($userId);
			echo "<div class='success'>Succesfully turned off 2fa for $name</div>";
		}elseif($_REQUEST['action'] == 'Change to e-mail'){
			add_user_meta($userId, '2fa_methods', 'email');
			echo "<div class='success'>Succesfully changed the 2fa factor for $name to e-mail</div>";
		}

		do_action('sim-login-settings-save', $userId, $name);
	}
				
	//Content
	?>
	<div id="login-info" class="tabcontent hidden">
		<h3>User login</h3>

		<?php
		if(in_array('usermanagement', wp_get_current_user()->roles)){
			wp_enqueue_script( 'sim_user_management');
			?>
			<form data-reset=1 class='sim-form'>
				<input type="hidden" name="disable-user-account"		value="<?php echo wp_create_nonce("disable-user-account");?>">
				<input type="hidden" name="user-id"					value="<?php echo $userId; ?>">
				<input type="hidden" name="action"					value="<?php echo $actionText;?>_useraccount">

				<p style="margin:30px 0px 0px;">
					Click the button below if you want to <?php echo $actionText;?> the useraccount for <?php echo $name;?>.
				</p>

				<?php echo SIM\addSaveButton('disable-user-account', ucfirst($actionText)." useraccount for $name");?>
			</form>
			<?php
		}
		
		if(function_exists('SIM\LOGIN\passwordResetForm')){
			echo SIM\LOGIN\passwordResetForm($user);
		}
		
		$methods	= get_user_meta($userId, '2fa_methods');
		$nonce		= wp_create_nonce( "wp-2fa-reset-nonce_$userId" );
		if(is_array($methods)){

			?>
			<div class="2FA" style="margin-top:30px;">
				<?php
				if(!in_array('email', $methods)){
					?>
					<form method='post'>
						<input type='hidden' name='user-id' value='<?php echo $userId;?>'>
						<input type='hidden' name='wp-2fa-nonce' value='<?php echo $nonce;?>'>

						Use the button below to change the 2fa factor for <?php echo $name;?> to e-mail<br>
						<input type='submit' name='action' value='Change to e-mail' class='button small'>
					</form>
					<br>
					<?php
				}

				?>
				<br>
				<form method='post'>
					<input type='hidden' name='user-id' value='<?php echo $userId;?>'>
					<input type='hidden' name='wp-2fa-nonce' value='<?php echo $nonce;?>'>

					Use the button below to turn off Two Factor Authentication for <?php echo $name;?><br>
					<input type='submit' name='action' value='Reset 2FA' class='button small'>
				</form>
			</div>
			<br>
		<?php
		}

		do_action('sim-after-login-settings', $userId, $nonce);
		?>
	</div>
	<?php
	return ob_get_clean();
}