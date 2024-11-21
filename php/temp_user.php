<?php
namespace SIM\USERMANAGEMENT;
use SIM;

// Only load when option is activated
if(!SIM\getModuleOption(MODULE_SLUG, 'tempuser')){
    return;
}

//Add expiry data column to users screen
add_filter( 'manage_users_columns', __NAMESPACE__.'\manageUserColumns');
function manageUserColumns( $columns ) {
    $columns['expiry_date'] = 'Expiry Date';
    return $columns;
}

//Add content to the expiry data column
add_filter( 'manage_users_custom_column', __NAMESPACE__.'\userCustomColumn', 10, 3);
function userCustomColumn( $val, $columnName, $userId ) {
    if($columnName != 'expiry_date'){
        return $val;
    }
    return get_user_meta( $userId, 'account_validity', true);
}

add_filter( 'manage_users_sortable_columns', __NAMESPACE__.'\sortableUserColumn' );
function sortableUserColumn( $columns ) {
    $columns['expiry_date'] = 'Expiry Date';

    return $columns;
}