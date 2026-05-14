<?php
namespace TSJIPPY\USERMANAGEMENT;
use TSJIPPY;
use TSJIPPY\ADMIN;

class AccountCreatedMail extends ADMIN\MailSetting{

    public \WP_User $user;
    public string $loginUrl;

    /**
     * Constructor
     *
     * @param \WP_User $user The user to send the email to
     * @param string $loginUrl The url to the login page
     * @param string $validTill The date till the login link is valid
     *
     * @return void
     */
    public function __construct($user, $loginUrl='', $validTill='') {
        // call parent constructor
		parent::__construct('account_created', PLUGINSLUG);

        $this->addUser($user);

        $this->replaceArray['%login_url%']    = $loginUrl;
        $this->replaceArray['%user_name%']    = $user->user_login;
        $this->replaceArray['%valid_till%']    = $validTill;

        $this->defaultSubject    = 'We have created an account for you on %site_name%';

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage 	.= "We have created an account for you on  %site_name%.<br>";
		$this->defaultMessage 	.= "Please set a password using this <a href='%login_url%'>link</a>.<br>";
        $this->defaultMessage   .= "This link is valid till %valid_till%<br>";
        $this->defaultMessage 	.= 'Your username is: %user_name%.<br>';
        $this->defaultMessage 	.= 'If you have any problems, please contact us by replying to this e-mail.';
    }
}
