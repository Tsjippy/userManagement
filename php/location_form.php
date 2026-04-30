<?php
namespace TSJIPPY\USERMANAGEMENT;
use TSJIPPY;

//create birthday and anniversary events
add_filter('tsjippy_before_inserting_formdata', __NAMESPACE__.'\beforeSavingLocationFormData', 10, 2);
function beforeSavingLocationFormData($submission, $object){
	if($object->formData->slug != 'user_location'){
		return $submission;
	}
	
	//Get the old values from the db
	$oldLocation = get_user_meta( $object->userId, 'location', true );
	
	//Get the location from the post array
	$location = $_POST["location"];
	
	//Only update when needed and if valid coordinates
	if(is_array($location) && $location != $oldLocation && !empty($location['latitude']) && !empty($location['longitude'])){
		$latitude = $location['latitude'] = filter_var(
			$location['latitude'],
			FILTER_SANITIZE_NUMBER_FLOAT,
			FILTER_FLAG_ALLOW_FRACTION
		);
		
		$location['longitude'] = filter_var(
			$location['longitude'],
			FILTER_SANITIZE_NUMBER_FLOAT,
			FILTER_FLAG_ALLOW_FRACTION
		);
		
		$location['address'] = sanitize_text_field($location['address']);
		
		$family	= new TSJIPPY\FAMILY\Family();
		$family->updateFamilyMeta($object->userId, "location", $location);

		do_action('tsjippy_location_update', $object->userId, $location);
		
		TSJIPPY\printArray("Saved location for user id $object->userId");
	}elseif(isset($_POST["location"]) && (empty($location['latitude']) || empty($location['longitude']))){
		//Remove location from db if empty
		delete_user_meta( $object->userId, 'location');
		TSJIPPY\printArray("Deleted location for user id $object->userId");

		do_action('tsjippy_location_removal', $object->userId);
	}
	
	return $submission;
}