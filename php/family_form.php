<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Add availbale partners as default
add_filter( 'sim_add_form_multi_defaults', __NAMESPACE__.'\addMultiDefault', 10, 3);
function addMultiDefault($defaultArrayValues, $userId, $formName){
	if($formName != 'user_family'){
		return $defaultArrayValues;
	}
	
	$potentials	= new PotentialFamilyMembers($userId);

	$potentials->potentialParents();
	$defaultArrayValues['Potential fathers'] 	= $potentials->potentialFathers;
	$defaultArrayValues['Potential mothers'] 	= $potentials->potentialMothers;
	$defaultArrayValues['Potential spouses']	= $potentials->potentialSpouses();
	$defaultArrayValues['Potential children']	= $potentials->potentialChildren();
	
	return $defaultArrayValues;
}

//Save family
add_filter('sim_before_saving_formdata', __NAMESPACE__.'\beforeSavingFormData', 10, 2);
function beforeSavingFormData($formResults, $object){
	if($object->formData->name != 'user_family'){
		return $formResults;
	}

	$userId	= $object->userId;

	$family = new SIM\FAMILY\Family();

	// First update the partner
	$newPartner	= sanitize_text_field($_POST['partner']);
	$oldPartner	= $family->getPartner($userId);
	if($newPartner != $oldPartner){
		// remove old relationship
		$family->removeRelationShip($userId, $oldPartner);

		// Add new one
		$family->storeRelationship($userId, $newPartner, 'partner');
	}

	// Then the weddingdate
	$newDate	= sanitize_text_field($_POST['weddingdate']);
	$oldDate	= $family->getWeddingDate($userId);
	if($newDate != $oldDate){
		$family->updateWeddingDate($userId,  $newDate);

		do_action('sim-family-after-weddingdate-update', $userId, $oldDate, $newDate);
	}

	// Family Picture
	$newPicture	= sanitize_text_field($_POST['family_picture']);
	$oldPicture	= $family->getFamilyMeta($userId, 'family_picture');
	if($newPicture != $oldPicture){
		$family->updateFamilyMeta($userId, 'family_picture', $newPicture);

		// Do not show in picture gallery
		update_post_meta($newPicture, 'gallery_visibility', 'hide' );

		do_action('sim_update_family_picture', $userId, $newPicture);
	}

	// Children
	$prevChildren	= $family->getChildren($userId);
	$newChildren	= $_POST['children'];
	$remove			= array_diff($prevChildren, $newChildren);
	$add			= array_diff($newChildren, $prevChildren);

	foreach($add as $child){
		// Add new one
		$family->storeRelationship($userId, $child, 'child');
	}

	foreach($remove as $child){
		// remove old relationship
		$family->removeRelationShip($userId, $child);
	}

	// Siblings
	$prevSiblings	= $family->getSiblings($userId);
	$newSiblings	= $_POST['siblings'];
	$remove			= array_diff($prevSiblings, $newSiblings);
	$add			= array_diff($newSiblings, $prevSiblings);

	foreach($add as $sibling){
		// Add new one
		$family->storeRelationship($userId, $sibling, 'sibling');
	}

	foreach($remove as $sibling){
		// remove old relationship
		$family->removeRelationShip($userId, $sibling);
	}

	// Family Name
	$newName	= sanitize_text_field($_POST['family_name']);
	$oldName	= $family->getFamilyMeta($userId, 'family_name');
	if($newName != $oldName){
		$family->updateFamilyMeta($userId, 'family_name', $newName);
	}

	return $formResults;
}

// add a family member modal
add_filter('sim_before_form', __NAMESPACE__.'\beforeForm', 10, 2);
function beforeForm($html, $formName){
	if($formName != 'user_family'){
		return $html;
	}
	
	if(isset($_GET['user-id'])){
		$lastname = get_userdata($_GET['user-id'])->last_name;
	}else{
		$lastname = wp_get_current_user()->last_name;
	}

	ob_start();
		
	?>
	<div id='add-account-modal' class="modal hidden">
		<div class="modal-content">
			<span class="close">&times;</span>
			<form action="" method="post" id="add-member-form">
				<p>Please fill in the form to create a user profile for a family member</p>
								
				<label>
					<h4>First name</h4>
					<input type="text"  class='wide' name="first-name">
				</label>
				
				<label>
					<h4>Last name</h4>
					<input type="text" name="last-name"  class='wide' value="<?php echo $lastname;?>">
				</label>
				
				<label>
					<h4>E-mail</h4>
					<input type="email"  class='wide' name="email">
				</label>
				
				<?php echo SIM\addSaveButton('adduseraccount', 'Add family member');?>
			</form>
		</div>
	</div>
	<?php

	return $html.ob_get_clean();
}