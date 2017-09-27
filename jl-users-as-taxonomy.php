<?php
/**
 * Plugin Name: Users as Taxonomy
 * Plugin URI: https://jasonlawton.com
 * Description: Create a taxonomy out of the user list
 * Version: 1.0.0
 * Author: Jason Lawton
 * Author URI: https://jasonlawton.com
 * License: MIT
 */
 
define( 'JL_UAT_TAXONOMY', 'userlist' );
define( 'JL_UAT_META_KEY', 'user_id' );
 // create the taxonomy

// Register Custom Taxonomy
function jl_uat_create_userlist_taxonomy() {
    
    $labels = array(
        'name'                       => 'Users',
        'singular_name'              => 'User',
        'menu_name'                  => 'User List',
        'all_items'                  => 'All Users',
        'parent_item'                => 'Parent User',
        'parent_item_colon'          => 'Parent User:',
        'new_item_name'              => 'New User',
        'add_new_item'               => 'Add New User',
        'edit_item'                  => 'Edit User',
        'update_item'                => 'Update User',
        'view_item'                  => 'View User',
        'separate_items_with_commas' => 'Separate users with commas',
        'add_or_remove_items'        => 'Add or remove users',
        'choose_from_most_used'      => 'Choose from the most used',
        'popular_items'              => 'Popular Users',
        'search_items'               => 'Search Users',
        'not_found'                  => 'Not Found',
        'no_terms'                   => 'No users',
        'items_list'                 => 'Users list',
        'items_list_navigation'      => 'Users list navigation',
    );
    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => false,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
    );
    register_taxonomy( JL_UAT_TAXONOMY, array( 'post' ), $args );

}
add_action( 'init', 'jl_uat_create_userlist_taxonomy', 0 );

// add hook to add a term when a user is added to the system (user_register - ID is passed as param)
function jl_uat_add( $user_id ) {
    /**
     * Data that we have access to
     * ID
     * user_login
     * user_pass
     * user_nicename
     * user_email
     * user_url
     * user_registered
     * display_name
     */

     $user = get_userdata( $user_id );

    $term = wp_insert_term( $user->display_name, JL_UAT_TAXONOMY );

    if ( !is_wp_error( $term ) ) {
        // add term meta, the user id
        add_term_meta( $term['term_id'], JL_UAT_META_KEY, $user_id, true );
    } else {
        var_dump('ERROR');
        var_dump($term);
        wp_die();
    }
}
add_action( 'user_register', 'jl_uat_add', 0 );

// add hook to add a term when a user is removed (delete_user action - ID is passed as param)
function jl_uat_delete( $user_id ) {
    $term = find_term_by_user_id( $user_id );
    wp_delete_term( $term->term_id, JL_UAT_TAXONOMY );
}
add_action( 'delete_user', 'jl_uat_delete', 0 );

// add hook to update term when a user is changed (profile_update - ID is passed as param)
function jl_uat_update( $user_id ) {
    $user = get_userdata( $user_id );
    // get the term via the user_id meta
    $term = find_term_by_user_id( $user_id );
    wp_update_term( $term->term_id, JL_UAT_TAXONOMY, array( 'name' => $user->display_name ) );
}
add_action( 'profile_update', 'jl_uat_update', 0 );

// optionally add a settings page where we can associate the taxonomy with other post types
// 1.0 will limit to just 'post'

/**
 * Show the user id that is associated with this term, not editable
 *
 * @param Object $term
 * @param Object $taxonomy
 * @return void
 */
function edit_taxonomy_field( $term, $taxonomy ){
    // get current group
    $user_id = get_term_meta( $term->term_id, JL_UAT_META_KEY, true );
    
    ?><tr class="form-field term-group-wrap">
        <th scope="row"><label for="feature-group"><?php _e( 'User ID', '' ); ?></label></th>
        <td><?php echo $user_id; ?></td>
    </tr><?php
}
add_action( JL_UAT_TAXONOMY . '_edit_form_fields', 'edit_taxonomy_field', 10, 2 );

/**
 * Find a term by it's meta data user_id
 *
 * @param int $user_id
 * @return Object
 */
function find_term_by_user_id( $user_id ) {
    $args = array(
        'hide_empty' => false, // also retrieve terms which are not used yet
        'meta_query' => array(
            array(
               'key'       => JL_UAT_META_KEY,
               'value'     => $user_id,
            //    'compare'   => ''
            )
        )
    );

    $terms = get_terms( JL_UAT_TAXONOMY, $args );

    return $terms[0];
}