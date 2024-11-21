<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action( 'rest_api_init', __NAMESPACE__.'\blockRestApiInit');
function blockRestApiInit() {
	// show reminders
	register_rest_route(
		RESTAPIPREFIX.'/usermanagement',
		'/show_reminders',
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\expiryWarnings',
			'permission_callback' 	=> '__return_true',
		)
	);
} 