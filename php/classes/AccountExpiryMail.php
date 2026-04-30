<?php
namespace TSJIPPY\USERMANAGEMENT;
use TSJIPPY;
use TSJIPPY\ADMIN;

class AccountExpiryMail extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct('account_expiry', PLUGINSLUG);

        $this->addUser($user);
        
		$expiryDate		                        = date(DATEFORMAT, strtotime(" +1 months"));
        $this->replaceArray['%expiry_date%']    = $expiryDate;

        $this->defaultSubject    = 'Your account will expire on %expiry_date%';

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage 	.= 'This is to inform you that your account on %site_name% will expire on %expiry_date%.<br>';
		$this->defaultMessage 	.= 'If you think this should be extended you can contact the STA coordinator (cc).';
    }
}

