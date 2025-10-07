<?php
namespace SIM\USERMANAGEMENT;
use SIM;

// add extra question to the new user form
add_action('sim_after_user_create_form', __NAMESPACE__.'\afterUserCreateForm');
function afterUserCreateForm(){
    echo "<label>";
        echo '<h4>User roles<span class="required">*</span></h4>';
    echo "</label>";
    echo displayRoles();
}

// store the results of the form above
add_action('sim_approved_user', __NAMESPACE__.'\userApproved');
function userApproved($userId){
    update_user_meta($userId, 'visa_info', $_POST['visa-info']);
}