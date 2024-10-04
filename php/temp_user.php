<?php
namespace SIM\USERMANAGEMENT;
use SIM;

// Only load when option is activated
if(!SIM\getModuleOption(MODULE_SLUG, 'tempuser')){
    return;
}

//Add expiry data column to users screen
add_filter( 'manage_users_columns', function( $columns ) {
    $columns['expiry_date'] = 'Expiry Date';
    return $columns;
});

//Add content to the expiry data column
add_filter( 'manage_users_custom_column', function ( $val, $columnName, $userId ) {
    if($columnName != 'expiry_date'){
        return $val;
    }
    return get_user_meta( $userId, 'account_validity', true);
}, 10, 3);

add_filter( 'manage_users_sortable_columns', function ( $columns ) {
    $columns['expiry_date'] = 'Expiry Date';

    return $columns;
} );