<?php
namespace SIM\USERMANAGEMENT;
use SIM;

class DashboardWarnings{
    public $reminderCount;
    public $reminderHtml;
    public $userId;

    public function __construct($userId){
        $this->reminderCount    = 0;
        $this->reminderHtml     = '';
        $this->userId           = $userId;

        do_action('sim-dashboard-waring-construct', $this);

        $this->vaccinationReminders();
    }
	
	/**
     * Checks for vaccination reminders of the current user
     * and his or her children
     */
    public function vaccinationReminders(){
        $vaccinationReminderHtml = vaccinationReminders($this->userId);
	
        if (!empty($vaccinationReminderHtml)){
            $this->reminderCount += 1;
            $this->reminderHtml .= $vaccinationReminderHtml;
        }
        
        //Check for children
        $family = get_user_meta($this->userId, "family", true);

        //User has children
        if (isset($family["children"])){
            foreach($family["children"] as $child){
                $result = vaccinationReminders($child);
                if (!empty($result)){
                    $this->reminderCount++;
                    $userdata 		        = get_userdata($child);
                    $this->reminderHtml	    .= str_replace("Your", $userdata->first_name."'s", $result);
                }
            }
        }
    }
}
