<?php
namespace TSJIPPY\USERMANAGEMENT;
use TSJIPPY;

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\loadAssets', 99);
function loadAssets(){
    wp_register_style( 'tsjippy_useraccount', TSJIPPY\pathToUrl(PLUGINPATH.'css/account.min.css'), array(), PLUGINVERSION);

    wp_register_script( 'tsjippy_user_management', TSJIPPY\pathToUrl(PLUGINPATH.'js/user_management.min.js'), array('tsjippy_formsubmit_script'), PLUGINVERSION, true);

    wp_register_script( 'tsjippy_userpage', TSJIPPY\pathToUrl(PLUGINPATH.'js/userpage.min.js'), array(), PLUGINVERSION, true);
}