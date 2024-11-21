<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action('init', __NAMESPACE__.'\blockInit');
function blockInit() {
	register_block_type(
		__DIR__ . '/reminders/build',
		array(
			'render_callback' => __NAMESPACE__.'\expiryWarnings',
		)
	);
}