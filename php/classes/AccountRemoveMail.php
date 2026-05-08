<?php
namespace TSJIPPY\USERMANAGEMENT;
use TSJIPPY;
use TSJIPPY\ADMIN;

class AccountRemoveMail extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct('account_removal', PLUGINSLUG);

        $this->addUser($user);

        $url                    = get_permalink(SETTINGS['account_page'] ?? '');
        if($url){
            $this->replaceArray['%account_page%']    = $url;
        }

        $this->defaultSubject    = 'Your account on %site_name% has been deleted';

        $this->defaultMessage    = 'Dear %full_name%,<br><br>';
        $this->defaultMessage   .= 'This is to inform you that your account on %site_name% has been deleted.<br>';
        $this->defaultMessage   .= 'You are no longer able to login.';
    }
}

