<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style( 'sim_useraccount', SIM\pathToUrl(MODULE_PATH.'css/account.min.css'), array(), MODULE_VERSION);

    wp_register_script( 'sim_user_management', SIM\pathToUrl(MODULE_PATH.'js/user_management.min.js'), array('sim_formsubmit_script'), MODULE_VERSION, true);

    wp_register_script( 'sim_userpage', SIM\pathToUrl(MODULE_PATH.'js/userpage.min.js'), array(), MODULE_VERSION, true);
}, 99);