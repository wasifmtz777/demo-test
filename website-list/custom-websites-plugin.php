<?php
/*
Plugin Name: Custom Websites Plugin
Description: A plugin to show website lists and their source codes.
Version: 1.0
Author: Wasig
*/

/* Register the custom post type */
function create_websites_post_type() {
    $labels = array(
        'name'               => _x( 'Websites', 'post type general name', 'custom-websites-plugin' ),
        'singular_name'      => _x( 'Website', 'post type singular name', 'custom-websites-plugin' ),
        'menu_name'          => _x( 'Websites', 'admin menu', 'custom-websites-plugin' ),
        'name_admin_bar'     => _x( 'Website', 'add new on admin bar', 'custom-websites-plugin' ),
        'add_new'            => _x( 'Add New', 'website', 'custom-websites-plugin' ),
        'add_new_item'       => __( 'Add New Website', 'custom-websites-plugin' ),
        'new_item'           => __( 'New Website', 'custom-websites-plugin' ),
        'edit_item'          => __( 'Edit Website', 'custom-websites-plugin' ),
        'view_item'          => __( 'View Website', 'custom-websites-plugin' ),
        'all_items'          => __( 'All Websites', 'custom-websites-plugin' ),
        'search_items'       => __( 'Search Websites', 'custom-websites-plugin' ),
        'parent_item_colon'  => __( 'Parent Websites:', 'custom-websites-plugin' ),
        'not_found'          => __( 'No websites found.', 'custom-websites-plugin' ),
        'not_found_in_trash' => __( 'No websites found in Trash.', 'custom-websites-plugin' )
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => false, // Disable direct querying of websites
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'websites' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title' )
    );

    register_post_type( 'websites', $args );
}
add_action( 'init', 'create_websites_post_type' );

/* Remove ability to create new websites */
function disable_websites_post_type() {
    global $wp_post_types;
    $wp_post_types['websites']->cap->create_posts = 'do_not_allow';
}
add_action('init', 'disable_websites_post_type');

/* Creating form to be used for users to provide data */
function custom_website_form() {
    ?>
    <form action="<?php echo esc_url( home_url( '/wp-admin/admin-post.php' ) ); ?>" method="post">
        <input type="hidden" name="action" value="submit_website_form">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required><br>
        <label for="website_url">Website URL:</label>
        <input type="url" id="website_url" name="website_url" required><br>
        <input type="submit" value="Submit">
    </form>
    <?php
}
add_shortcode('custom_website_form', 'custom_website_form');


/* Storing all form submissions  */
function submit_website_form() {
    if (isset($_POST['name']) && isset($_POST['website_url'])) {
        $name = sanitize_text_field($_POST['name']);
        $website_url = esc_url_raw($_POST['website_url']);

        $post_id = wp_insert_post(array(
            'post_title' => $name,
            'post_type' => 'websites',
            'post_status' => 'publish'
        ));

        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, 'website_url', $website_url);
            $message = 'Submission successful!';
        } else {
            $message = 'Submission failed. Please try again.';
        }
    } else {
        $message = 'All fields are required.';
    }

    // Display alert message
    echo '<script>alert("' . $message . '");</script>';

    // Redirect to home page
    wp_redirect(home_url());
    exit;
}
add_action('admin_post_submit_website_form', 'submit_website_form');
add_action('admin_post_nopriv_submit_website_form', 'submit_website_form'); 
// Handle non-logged-in users


/* Callback function for website source code metabox */
function website_source_code_metabox_callback($post) {
    $website_url = get_post_meta($post->ID, 'website_url', true);
    if ($website_url) {
        echo '<p><strong>Website URL:</strong> <a href="' . esc_url($website_url) . '" target="_blank">' . esc_html($website_url) . '</a></p>';
        
        $website_source = wp_remote_get($website_url);
        if (!is_wp_error($website_source) && $website_source['response']['code'] == 200) {
            $website_html = wp_remote_retrieve_body($website_source);
            ?>
            <textarea rows="10" cols="50" readonly><?php echo esc_html($website_html); ?></textarea>
            <?php
        } else {
            echo 'Failed to fetch website source code. The server returned a non-200 response.';
        }
    } else {
        echo 'Website URL is not provided.';
    }
}

/* Customize edit screen for websites */
function customize_websites_edit_screen() {
    remove_post_type_support('websites', 'editor'); // Remove editor
    remove_post_type_support('websites', 'author'); // Remove author
    remove_post_type_support('websites', 'thumbnail'); // Remove featured image
    remove_post_type_support('websites', 'excerpt'); // Remove excerpt
    remove_post_type_support('websites', 'comments'); // Remove comments
    
    // Add custom metabox for source code
    add_meta_box('website_source_code_metabox', 'Website Source Code', 'website_source_code_metabox_callback', 'websites', 'normal', 'default');
}
add_action('add_meta_boxes', 'customize_websites_edit_screen');
