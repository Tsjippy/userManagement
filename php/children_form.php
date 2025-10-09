<?php
namespace SIM\USERMANAGEMENT;
use SIM;

/**
 * Displays the forms for children
 */
function showChildrenFields($childId){
	$availableForms		= (array)SIM\getModuleOption(MODULE_SLUG, 'enabled-forms');

	ob_start();
	$active	= 'active';
	$hidden	= '';
	if(in_array('generic', $availableForms)){
		echo "<button class=' button tablink $active' id='show-generic-child-info-$childId' data-target='generic-child-info-$childId'>Generic info</button>";
		$active= '';
	}

	if(in_array('vaccinations', $availableForms)){
		echo "<button class='button tablink $active' id='show-generic-child-info-$childId' data-target='generic-child-info-$childId'>Vaccinations</button>";
		$active= '';
	}

	if(in_array('profile picture', $availableForms)){
		echo "<button class='button tablink $active' id='show-profile-picture-child-info-$childId' data-target='profile-picture-child-info-$childId'>Profile picture</button>";
	}
	
	if(in_array('generic', $availableForms)){
		?>
		<div id='generic-child-info-<?php echo $childId;?>' class='tabcontent'>
			<?php echo do_shortcode("[formbuilder formname=child_generic user-id=$childId]"); ?>
		</div>
		<?php

		$hidden	= 'hidden';
	}

	if(in_array('vaccinations', $availableForms)){
		echo	"<div id='generic-child-info-$childId' class='tabcontent $hidden'>";
			echo do_shortcode("[formbuilder formname=user_medical user-id=$childId]");
		echo	"</div>";

		$hidden	= 'hidden';
	}

	if(in_array('profile picture', $availableForms)){
		echo	"<div id='profile-picture-child-info-$childId' class='tabcontent $hidden'>";
			echo do_shortcode("[formbuilder formname=profile_picture user-id='$childId']");
		echo	"</div>";
	}

	return ob_get_clean();
}