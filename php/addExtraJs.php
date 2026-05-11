<?php
namespace TSJIPPY\USERMANAGEMENT;
use TSJIPPY;

/* HELPER FUNCTIONS */
//add special js to the dynamic form js
add_filter('tsjippy_form_extra_js', __NAMESPACE__.'\addJs', 10, 3);
/**
 * Add extra JavaScript for all js files found in the js folder
 * 
 * @param string 	$js			The existing JavaScript code for the form
 * @param object 	$object		The form object containing the form data
 * @param bool 		$minimized	Whether to load the minimized version of the JavaScript file
 * 
 * @return string				The updated JavaScript code with the extra code added
 */
function addJs($js, $object, $minimized){
	$path	= plugin_dir_path( __DIR__)."js/{$object->formData->slug}.min.js";
	if(!$minimized || !file_exists($path)){
		$path	= plugin_dir_path( __DIR__)."js/{$object->formData->slug}.js";
	}

	if(file_exists($path)){
		$js		= file_get_contents($path);
	}

	return $js;
}