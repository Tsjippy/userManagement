<?php
namespace TSJIPPY\USERMANAGEMENT;
use TSJIPPY;

use function TSJIPPY\addElement;
use function TSJIPPY\addRawHtml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends TSJIPPY\ADMIN\SubAdminMenu{

    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
        $label  = addElement('label', $parent, [], 'Enable temporary user accounts');

        addElement(
            'input', 
            $label, 
            [
                'type'    => 'checkbox',
                'name'    => 'tempuser',
                'value'   => '1',
                'checked' => isset($this->settings['tempuser']) ? 'checked' : ''
            ], 
            '', 
            'afterBegin'
        );

        addElement('br', $parent);

        addElement('br', $parent);

        $this->recurrenceSelector("check-details-mail-freq", $this->settings['check-details-mail-freq'], 'How often should people asked to check their details for changes?', $parent);

        addElement('br', $parent);

        addElement('label', $parent, [], 'Select any forms you want to be available on the account page');
        addElement('br', $parent);

        foreach(['family', 'generic', 'location', 'profile picture', 'security'] as $form){
            $label  = addElement('label', $parent, [], ucfirst($form));

            addElement('br', $parent);

            $attributes = [
                'type'    => 'checkbox',
                'name'    => 'enabled-forms[]',
                'value'   => $form
            ];

            if(in_array($form, $this->settings['enabled-forms'] ?? [] )){
                $attributes['checked'] = 'checked';
            }

            addElement(
                'input', 
                $label, 
                $attributes, 
                '', 
                'afterBegin'
            );
        }
        return true;
    }

    public function emails($parent){
        $tab      = 'account-approved-email';
        if(isset($_GET['second-tab'])){
            $tab  = sanitize_key($_GET['second-tab']);
        }

        $tablinkWrapper = addElement('div', $parent, ['class' => 'tablink-wrapper']);

        $buttons    = [
            'account-approved-email'        => 'Account Approved',
            'account-created-email'         => 'Account Approved',
            'account-will-expired-email'    => 'Account Will Expire',
            'account-deleted-email'         => 'Account Deleted',
            'not-seen-email'                => 'No Activity'
        ];

        foreach($buttons as $id => $text){
            $attributes = [
                'class'       => 'tablink' . ($tab == $id ? ' active' : ''),
                'id'          => "show-$id",
                'data-target' => $id,
                'type'        => 'button'
            ];
            addElement('button', $tablinkWrapper, $attributes, $text);
        }

        ob_start();
        ?>
        <div id="account-approved-email" class="tabcontent <?php echo $tab != 'account-approved-email' ? 'hidden' : '';?>">
            <h4>E-mail to people who's account is just approved</h4>
            <label>Define the e-mail people get when they are added to the website</label>
            <?php
            $accountApproveddMail    = new AccountApproveddMail(wp_get_current_user());
            $accountApproveddMail->printPlaceholders();
            $accountApproveddMail->printInputs();
            ?>
        </div>

        <div id="account-created-email" class="tabcontent <?php echo $tab != 'account-created-email' ? 'hidden' : '';?>">
            <h4>E-mail to people who's account is just created</h4>
            <label>Define the e-mail people get when they are added to the website</label>
            <?php
            $accountCreatedMail    = new AccountCreatedMail(wp_get_current_user());
            $accountCreatedMail->printPlaceholders();
            $accountCreatedMail->printInputs();
            ?>
        </div>

        <div id="account-will-expired-email" class="tabcontent <?php echo $tab != 'account-will-expired-email' ? 'hidden' : '';?>">
            <h4>E-mail to people who's account is about to expire</h4>
            <label>Define the e-mail people get when they are about to be removed from the website</label>
            <?php
            $accountExpiryMail    = new AccountExpiryMail(wp_get_current_user());
            $accountExpiryMail->printPlaceholders();
            $accountExpiryMail->printInputs();
            ?>
        </div>

        <div id="account-deleted-email" class="tabcontent <?php echo $tab != 'account-deleted-email' ? 'hidden' : '';?>">
            <h4>E-mail to people who's account is deleted</h4>
            <label>Define the e-mail people get when they are removed from the website</label>
            <?php
            $accountRemoveMail    = new AccountRemoveMail(wp_get_current_user());
            $accountRemoveMail->printPlaceholders();
            $accountRemoveMail->printInputs();
            ?>
        </div>

        <div id="not-seen-email" class="tabcontent <?php echo $tab != 'not-seen-email' ? 'hidden' : '';?>">
            <h4>E-mail to people who have not logged in for more than a year</h4>
            <label>Define the e-mail people get when they have not logged into the website for more than a year</label>
            <?php
            $weMissYouMail    = new WeMissYouMail(wp_get_current_user());
            $weMissYouMail->printPlaceholders();
            $weMissYouMail->printInputs();
            ?>
        </div>
        <?php

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function data($parent=''){

        return false;
    }

    public function functions($parent){

        return false;
    }

    /**
     * Function to do extra actions from $_POST data. Overwrite if needed
     */
    public function postActions(){
        return '';
    }

    /**
     * Schedules the tasks for this plugin
     *
    */
    public function postSettingsSave(){
        scheduleTasks();
    }
}