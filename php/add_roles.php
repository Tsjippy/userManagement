<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_filter('sim_module_updated', __NAMESPACE__.'\addRoles', 10, 2);
function addRoles($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	// Only add the new role if it does not exist
	if(!wp_roles()->is_role( 'rolemanagement' )){
		$roleSet 					= get_role( 'contributor' )->capabilities;
		$roleSet['edit_users']		= true;
		$roleSet['list_users']		= true;
		$roleSet['promote_users']	= true;

		add_role(
			'rolemanagement',
			'Role Manager',
			$roleSet
		);
	}

	if(!wp_roles()->is_role( 'usermanagement' )){
		$roleSet 					= get_role( 'contributor' )->capabilities;
		$roleSet['edit_users']		= true;
		$roleSet['list_users']		= true;
		$roleSet['remove_users']	= true;
		$roleSet['promote_users']	= true;
		
		add_role(
			'usermanagement',
			'User Manager',
			$roleSet
		);
	}

	return $options;
}

add_filter('sim_role_description', __NAMESPACE__.'\roleDescription', 10, 2);
function roleDescription($description, $role){
    if($role == 'rolemanagement'){
		return 'Ability to grant people an extra role';
	}
	if($role == 'usermanagement'){
		return 'Ability to edit other user accounts';
    }
    return $description;
}