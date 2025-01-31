<?php
namespace SIM\USERMANAGEMENT;
use SIM;

/* HELPER FUNCTIONS */
//add special js to the dynamic form js
add_filter('sim_form_extra_js', __NAMESPACE__.'\addJs', 10, 3);
function addJs($js, $object, $minimized){
	$path	= plugin_dir_path( __DIR__)."js/$object->formName.min.js";
	if(!$minimized || !file_exists($path)){
		$path	= plugin_dir_path( __DIR__)."js/$object->formName.js";
	}

	if(file_exists($path)){
		$js		= file_get_contents($path);
	}

	return $js;
}