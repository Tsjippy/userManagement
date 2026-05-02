<?php
namespace TSJIPPY\USERMANAGEMENT;
use TSJIPPY;

class DashboardWarnings{
    public $reminderCount;
    public $reminderHtml;
    public $userId;

    public function __construct($userId){
        $this->reminderCount    = 0;
        $this->reminderHtml     = '';
        $this->userId           = $userId;

        do_action('tsjippy-dashboard-waring-construct', $this);
    }
}
