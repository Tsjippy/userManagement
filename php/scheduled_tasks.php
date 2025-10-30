<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action('init', __NAMESPACE__.'\taskInit');
function taskInit(){
	//add action for use in scheduled task
	add_action( 'birthday_check_action', __NAMESPACE__.'\birthdayCheck' );
    add_action( 'vaccination_reminder_action', __NAMESPACE__.'\vaccinationReminder' );
    add_action( 'check-details-mail_action', __NAMESPACE__.'\checkDetailsMail' );
    add_action( 'account_expiry_check_action', __NAMESPACE__.'\accountExpiryCheck' );
	add_action( 'check_last_login_date_action', __NAMESPACE__.'\checkLastLoginDate' );
}

function scheduleTasks(){
    SIM\scheduleTask('birthday_check_action', 'daily');
    SIM\scheduleTask('account_expiry_check_action', 'daily');
    SIM\scheduleTask('vaccination_reminder_action', 'monthly');
	SIM\scheduleTask('check_last_login_date_action', 'monthly');

	$freq	= SIM\getModuleOption(MODULE_SLUG, 'check-details-mail-freq');
	if($freq){
		SIM\scheduleTask('check-details-mail_action', $freq);
	}
}

function birthdayCheck(){
	//Change the user to the admin account otherwise get_users will not work
	wp_set_current_user(1);

	//Current date time
	$date   = new \DateTime();

	//Get all the birthday users of today
	$users = get_users(array(
		'meta_key'     => 'birthday',
		'meta_value'   => $date->format('-m-d'),
		'meta_compare' => 'LIKE',
	));

	foreach($users as $user){
		$userId 	= $user->ID;
		$firstName 	= $user->first_name;

		$family = new SIM\FAMILY\Family();

		//Send birthday wish to the user
		add_action(
			'sim-user-management-birthday-message',
			"Hi $firstName,\nCongratulations with your birthday!", 
			$userId
		);

		//Send to parents
		if ($family->isChild($userId)){
			$childTitle = SIM\getChildTitle($user->ID);

			$message = "Congratulations with the birthday of your $childTitle ".get_userdata($user->ID)->first_name;
		}

		foreach($family->getParents($userId) as $parent){
			add_action(
				'sim-user-management-birthday-message',
				"Hi ".get_userdata($parent)->first_name.",\n$message",
				$parent
			);
		}
	}
}

/**
 * loop over all users and scan for expiry vaccinations
 */
function vaccinationReminder(){
	$family = new SIM\FAMILY\Family();

	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);

	//Retrieve all users
	$users = get_users( array( 'fields' => array( 'ID','user_login','display_name' ) ) );

	//loop over the users
	foreach($users as $user){
		$reminderHtml = vaccinationReminders($user->ID);

		//If there are reminders, send an e-mail
		if (!empty($reminderHtml)){
			$userdata = get_userdata($user->ID);
			if($userdata != null){
				$parents 	= $family->getParents($user->ID);
				$recipients = '';

				//Is child
				if($parents){

					$reminderHtml = str_replace("Your", $userdata->first_name."'s", $reminderHtml);

					$vaccinationWarningMail    	= new AdultVaccinationWarningMail($userdata);
					$vaccinationWarningMail->filterMail();
					$subject					= $vaccinationWarningMail->subject;
					$message					= $vaccinationWarningMail->message;

					foreach($parents as $parent){
						if(!str_contains($parent->user_email,'.empty')){
							if(!empty($recipients)){
								$recipients .= ', ';
							}
							$recipients .= $parent->user_email;
						}
					}
				//not a child
				}else{
					//If this not a valid email skip this email
					if(!str_contains($userdata->user_email,'.empty')){
						continue;
					}

					$vaccinationWarningMail    	= new AdultVaccinationWarningMail($userdata);
					$vaccinationWarningMail->filterMail();
					$subject					= $vaccinationWarningMail->subject;
					$message					= $vaccinationWarningMail->message;
					$headers					= $vaccinationWarningMail->headers;
				}


				if(!empty($recipients)){
					//Get the current health coordinator
					$healtCoordinators 			= get_users( array( 'fields' => array( 'ID','display_name' ),'role' => 'medicalinfo' ));
					if($healtCoordinators != null){
						$healtCoordinator = (object)$healtCoordinators[0];
					}else{
						$healtCoordinator = new \stdClass();
						$healtCoordinator->display_name = '';
						error_log("Please assign someone the health coorodinator role!");
					}

					//Send the mail
					wp_mail($recipients , $subject, $message, $headers );
				}
			}
		}
	}
}

/**
 * Get the vaccination reminder html of an user
 *
 * @param	int		$userId	WP_User id
 *
 * @return	string	The html
 */
function vaccinationReminders($userId){
	//Get the current users medical data
	$medicalUserInfo = (array)get_user_meta( $userId, "medical",true);

	$reminderHtml = "";
	foreach($medicalUserInfo as $key=>$info){
		if (str_contains($key, 'expiry_date')) {
			//Its an array, so another vaccination
			if(is_array($info)){
				foreach($info as $date_key=>$date){
					//Get the vaccination name of this other vaccination
					$vaccinationName = $medicalUserInfo['other_vaccination'][$date_key];
					if($date != ""){
						$reminderHtml .= checkExpiryDate($date, "$vaccinationName vaccination");
					}
				}
			}else{
				//Get the clean vaccination name
				$vaccinationName = str_replace('expiry_date_of_your_','', $key);
				$vaccinationName = str_replace('_vaccination', '', $vaccinationName);
				$vaccinationName = ucwords(str_replace('_', ' ', $vaccinationName));
				$reminderHtml .= checkExpiryDate($info, "$vaccinationName vaccination");
			}
		}
	}

	return $reminderHtml;
}

/**
 * Check expiry date of all vacination
 *
 * @param	string	$date			The expiry date
 * @param	string	$expiryName		The name of the vaccination
 *
 * @return	string					Html listing all vaccination who are expired
 */
function checkExpiryDate($date, $expiryName){
	$vaccinationWarningTime	= SIM\getModuleOption(MODULE_SLUG, 'vaccination-warning-time');
	if ($vaccinationWarningTime && !empty($date)){
		$reminderHtml 	= "";

		//Vaccination expiry date
		try{
			$expiryDate = new \DateTime($date);
		}catch (\Exception $e) {
			return;
		}

		//Date of first warning
		$warningDate	= new \DateTime($date);
		$interval		= new \DateInterval('P'.$vaccinationWarningTime.'M');
		date_sub($warningDate, $interval);

		$now			= new \DateTime();

		$niceExpiryDate = $expiryDate->format('j F Y');

		//Expires today
		if($niceExpiryDate == $now->format('j F Y')){
			$reminderHtml .= "<li>Your $expiryName expires today.</li><br>";
		//In the past
		}elseif($expiryDate < $now){
			$reminderHtml .= "<li>Your $expiryName is expired on $niceExpiryDate. </li><br>";
		//In the near future
		}elseif($now >= $warningDate){
			$diff=date_diff(date_create(date("Y-m-d")), $expiryDate)->format("%a");
			if($diff == 1){
				$text = "tomorrow";
			}else{
				$text = "in $diff days on $niceExpiryDate";
			}
			$reminderHtml .= "<li>Your $expiryName will expire $text.</li><br>";
		}

		return $reminderHtml;
	}
}

/**
 * send an e-mail with an overview of an users details for them to check
 */
function checkDetailsMail(){
	wp_set_current_user(1);

	$family 	= new SIM\FAMILY\Family();

	$subject	= 'Please review your website profile';

	//Retrieve all users
	$users 			= SIM\getUserAccounts(false, true);

	$accountPageUrl	= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'account_page');

	if(empty($accountPageUrl)){
		SIM\printArray('No account page defined');
		return;
	}
	$baseUrl		= "$accountPageUrl?main-tab=";

	$styleString	= "style='text-decoration:none; color:#444;'";

	//Loop over the users
	foreach($users as $user){
		//Send e-mail
		$message  = "Hi {$user->first_name},<br><br>";
		$message .= 'Once a year we would like to remind you to keep your information on the website up to date.<br>';
		$message .= 'Please check the information below to see if it is still valid, if not update it.<br><br>';

		/*
		** PROFILE PICTURE
 		*/
		$message .= "<a href='{$baseUrl}profilePicture' $styleString><b>Profile picture</b></a><br>";
		$profilePicture	= getProfilePictureUrl($user->ID);
		if($profilePicture){
			$message 		.= "This is your profile picture:<br>";
			$message 		.= "<img src='$profilePicture' alt='$profilePicture' width='100px' height='100px'";
			$message 		.= "<br><br>";
		}else{
			$message .= "<table>";
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}profilePicture' $styleString>You have not uploaded a picture</a>";
					$message .= "</td>";
				$message .= "</tr>";
			$message .= "</table>";
		}
		$message .= "<br>";

		/*
		** PERSONAL DETAILS
 		*/
		$message .= "<a href='{$baseUrl}generic-info' $styleString><b>Personal details</b></a><br>";
		$message .= "<table>";
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "Name:";
				$message .= "</td>";
				$message .= "<td>";
					$message .= "$user->display_name";
				$message .= "</td>";
			$message .= "</tr>";

			$birthday = get_user_meta($user->ID, 'birthday', true);
			if(empty($birthday)){
				$birthday = 'No birthday specified.';
			}else{
				$birthday = date('d  F Y', strtotime($birthday));
			}
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "Birthday:";
				$message .= "</td>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}generic-info#birthday' $styleString>$birthday</a>";
				$message .= "</td>";
			$message .= "</tr>";

			$message	= apply_filters('sim-usermanagement-details-reminder-html', $message, $user, $baseUrl, $styleString);
		$message .= "</table>";
		$message .= "<br>";

		/*
		** PHONENUMBERS
 		*/
		$phonenumbers = (array)get_user_meta($user->ID, 'phonenumbers', true);
		array_filter($phonenumbers);
		$title	= 'Phonenumber';
		if(count($phonenumbers)>1){
			$title .= 's';
		}

		$message .= "<a href='{$baseUrl}generic-info' $styleString><b>$title</b></a><br>";
		$message .= "<table>";
		if(empty($phonenumbers)){
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}generic-info#phonenumbers[0]' $styleString>No phonenumbers provided</a>";
				$message .= "</td>";
			$message .= "</tr>";
		}elseif(count($phonenumbers) == 1){
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}generic-info#phonenumbers[0]' $styleString>".array_values($phonenumbers)[0].'</a>';
				$message .= "</td>";
			$message .= "</tr>";
		}else{
			foreach($phonenumbers as $key=>$number){
				$nr	= $key+1;
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Phonenumber $nr:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}generic-info#phonenumbers[$key]' $styleString>$number</a>";
					$message .= "</td>";
				$message .= "</tr>";
			}
		}
		$message .= "</table>";
		$message .= "<br>";

		/*
		** MINISTRIES
 		*/
		$userMinistries = (array)get_user_meta($user->ID, 'jobs', true);
		if(count($userMinistries)>1){
			$title	= 'Ministries';
		}else{
			$title	= 'Ministry';
		}
		$message .= "<a href='{$baseUrl}generic-info' $styleString><b>$title</b></a><br>";

		$message .= "<table>";
			array_filter($userMinistries);
			if(empty($userMinistries)){
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}generic-info#ministries[]' $styleString>No ministry provided</a>";
					$message .= "</td>";
				$message .= "</tr>";
			}else{
				foreach($userMinistries as $ministry=>$job){
					$message .= "<tr>";
						$message .= "<td>";
							$message .= get_the_title($ministry).":";
						$message .= "</td>";
						$message .= "<td>";
							$message .= "<a href='{$baseUrl}generic-info#ministries[]' $styleString>$job</a>";
						$message .= "</td>";
					$message .= "</tr>";
				}

			}
		$message .= "</table>";
		$message .= "<br>";

		/*
		** LOCATION
 		*/
		$message	.= "<a href='{$baseUrl}location' $styleString><b>Location</b></a><br>";
		$location	= (array)get_user_meta($user->ID, 'location', true);
		array_filter($location);
		if(empty($location['address'])){
			$location = "No location provided";
		}else{
			$location = $location['address'];
		}

		$message .= "<table>";
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}location#location[compound]' $styleString>$location</a>" ;
				$message .= "</td>";
			$message .= "</tr>";
		$message .= "</table>";
		$message .= "<br>";

		/*
		** FAMILY
 		*/
		$partner	= $family->getPartner($user->ID, true);
		$children	= $family->getChildren($user->ID);
		$siblings	= $family->getSiblings($user->ID);
		if($partner || $children || $siblings){
			$picture	= $family->getFamilyMeta($partner, 'picture');
			if($picture){
				$url		= wp_get_attachment_url($picture);
				$picture	= "<img src='$url' width=100 height=100>";
			}else{
				$picture	= "You have not uploaded a picture";
			}

			$weddingDate	= $family->getWeddingDate($user->ID);
			if(empty($family['partner'])){
				$partner 		= 'You have no spouse';
			}else{
				$partner = $partner->display_name;

				if($weddingDate){
					$text	= date('d F Y', strtotime($family['weddingdate']));
				}else{
					$text	= "No weddingdate provided";
				}

				$weddingDateHtml = "<tr>";
					$weddingDateHtml .= "<td>";
						$weddingDateHtml .= "Wedding date:";
					$weddingDateHtml .= "</td>";
					$weddingDateHtml .= "<td>";
						$weddingDateHtml .= "<a href='{$baseUrl}family#weddingdate' $styleString>$text</a>";
					$weddingDateHtml .= "</td>";
				$weddingDateHtml .= "</tr>";
			}

			$message .= "<a href='{$baseUrl}family' $styleString><b>Family details</b></a><br>";
			$message .= "<table>";
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Family picture:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}family#family[picture]' $styleString>$picture</a>";
					$message .= "</td>";
				$message .= "</tr>";

				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Spouse:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}family#family[partner]' $styleString>$partner</a>";
					$message .= "</td>";
				$message .= "</tr>";

				$message .= $weddingDateHtml;

				foreach($family['children'] as $key=>$child){
					$nr=$key+1;
					$message .= "<tr>";
						$message .= "<td>";
							$message .= "Child $nr:";
						$message .= "</td>";
						$message .= "<td>";
							$message .= "<a href='{$baseUrl}family#family[children][$key]' $styleString>".get_userdata($child)->display_name."</a>";
						$message .= "</td>";
					$message .= "</tr>";
				}
			$message .= "</table>";
		}

		$message .= '<br>';
		$message .= "If any information is not correct, please correct it on <a href='$accountPageUrl'>".str_replace(['https://www.','https://'], '', $accountPageUrl)."</a>.<br>Or just click on any details listed above.";
		wp_mail( $user->user_email, $subject, $message);
	}
}

/**
 * Notifies people that their account is about to expire or has been deleted
 */
function accountExpiryCheck(){
	require_once(ABSPATH.'wp-admin/includes/user.php');

	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);

	//Get the users who will expire in 1 month
	$users = get_users(
		array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' 		=> 'account_validity',
					'compare' 	=> 'EXISTS'
				),
				array(
					'key' 		=> 'account_validity',
					'value' 	=> 'unlimited',
					'compare' 	=> '!='
				),
				array(
					'key' 		=> 'account_validity',
					'value' 	=> date("Y-m-d", strtotime(" +1 months")),
					'compare' 	=> '=',
					'type' 		=> 'DATE'
				),

			),
		)
	);

	foreach($users as $user){
		//Send e-mail
		$accountExpiryMail    = new AccountExpiryMail($user);
		$accountExpiryMail->filterMail();

		//Send the mail if valid email
		if(!str_contains($user->user_email,'.empty')){
			$recipient = $user->user_email;
		}else{
			$recipient = '';
		}

		wp_mail( $recipient, $accountExpiryMail->subject, $accountExpiryMail->message, $accountExpiryMail->headers);
	}

	//Get the users who are expired
	$expiredUsers = get_users(
		array(
			'meta_query'	=> array(
				'relation' 		=> 'AND',
				array(
					'key' 		=> 'account_validity',
					'compare' 	=> 'EXISTS'
				),
				array(
					'key' 		=> 'account_validity',
					'value' 	=> 'unlimited',
					'compare'	=> '!='
				),
				array(
					'key'		=> 'account_validity',
					'value' 	=> date("Y-m-d"),
					'compare' 	=> '<=',
					'type' 		=> 'DATE'
				),

			),
		)
	);

	foreach($expiredUsers as $user){
		// check if it is a valid date string
		if(!strtotime(get_user_meta($user->ID, 'account_validity', true))){
			continue;
		}

		//Delete the account
		SIM\printArray("Deleting user with id $user->ID and name $user->display_name as it was a temporary account.");
		wp_delete_user($user->ID);
	}
}

/**
 * Send reminder to people to login
 */
function checkLastLoginDate(){
	wp_set_current_user(1);

	$users = SIM\getUserAccounts();
	foreach($users as $user){
		$lastLogin				= get_user_meta( $user->ID, 'last_login_date',true);

		//user has never logged in
		if(empty($lastLogin)){
			//Send e-mail
			$to = $user->user_email;

			//Skip if not valid email
			if(str_contains($to,'.empty')){
				continue;
			}

			$key		 = get_password_reset_key($user);
			if(is_wp_error($key)){
				return $key;
			}

			$pageUrl	 = get_permalink(SIM\getModuleOption('login', 'password-reset-page')[0]);
			$url		 = "$pageUrl?key=$key&login=$user->user_login";

			$mail = new AccountCreatedMail($user, $url);
			$mail->filterMail();

			wp_mail( $to, $mail->subject, $mail->message);
		}else{
			$lastLoginDate			= date_create($lastLogin);
			$now 					= new \DateTime();
			$yearsSinceLastLogin 	= date_diff($lastLoginDate, $now)->format("%y");

			//User has not logged in in the last year
			if($yearsSinceLastLogin > 0){
				//Send e-mail
				$to = $user->user_email;
				//Skip if not valid email
				if(str_contains($to, '.empty')){
					continue;
				}

				//Send e-mail
				$weMissYouMail    = new WeMissYouMail($user, $lastLogin);
				$weMissYouMail->filterMail();

				wp_mail( $to, $weMissYouMail->subject, $weMissYouMail->message);
			}
		}
	}

}
