<?php
/**
 * THNKbe4
 *
 * @package       LEARNDASHC
 * @author        Jeffrey Dean
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   THNKbe4
 * Plugin URI:    https://mydomain.com
 * Description:   Show expiration date on certificate
 * Version:       1.0.0
 * Author:        Jeffrey Dean
 * Author URI:    https://your-author-domain.com
 * Text Domain:   learndash-certificate-expirarity
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with THNKbe4. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HELPER COMMENT START
 * 
 * This file contains the logic required to run the plugin.
 * To add some functionality, you can simply define the WordPres hooks as followed: 
 * 
 * add_action( 'init', 'some_callback_function', 10 );
 * 
 * and call the callback function like this 
 * 
 * function some_callback_function(){}
 * 
 * HELPER COMMENT END
 */

// Include your custom code here.
// Add a filter to customize the navigation items
add_filter('wp_nav_menu_items', 'custom_nav_menu_items', 10, 2);

function custom_nav_menu_items($items, $args) {
    if (is_user_logged_in()) {
        $courses_url = home_url('/courses-2/');
        $profile_url = home_url('/profile/');
		$items .= '<li id="menu-item-3051" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-3051"><a href="'. $courses_url . '" aria-current="page">Courses</a></li>';
        $items .= '<li id="menu-item-3048" class="menu-item menu-item-type-post_type menu-item-object-page page-item-13 menu-item-3048"><a href="'. $profile_url . '" aria-current="page">Profile</a></li>';
    }

    return $items;
}

function redirect_default_page_to_login() {
    if (is_front_page() && !is_user_logged_in()) {
        wp_redirect(home_url('/log-in/'));
        exit;
    }
}
add_action('template_redirect', 'redirect_default_page_to_login');

function custom_aad_sso_login_redirect($user_login, $user) {
	$redirect_url = home_url('/');
	
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;

	if ( get_user_meta($user_id, 'pw_user_status', true )  === 'pending' || get_user_meta($user_id, 'pw_user_status', true )  === 'denied' ){
		$approved = false;
	} else {
		$approved = true;
	}
	
	if ($approved) {
		$redirect_url = home_url('/');
		wp_redirect($redirect_url);
	} else {
		wp_logout();
	    wp_clear_auth_cookie(); 
		$redirect_url = home_url('/your-registration-request-has-been-submitted/');
		wp_redirect($redirect_url);
	}
    
    exit;
}
add_action('wp_login', 'custom_aad_sso_login_redirect', 10, 2);


function logout_button_shortcode() {
	$redirect_url = home_url('/log-in/');
	$logout_url = wp_logout_url( $redirect_url );
    return '<a href="' . $logout_url . '" class="button" style="float: right;">Logout</a>';
}
add_shortcode('thnkb4_logout_button', 'logout_button_shortcode');

function disable_user_nav_menu() {
    if (current_user_can('subscriber')) {
        add_filter('show_admin_bar', '__return_false');
    }
}
add_action('after_setup_theme', 'disable_user_nav_menu');

add_filter('learndash_course_grid_ribbon_text', 'custom_ld_course_grid_ribbon_text', 10, 2);
function custom_ld_course_grid_ribbon_text($ribbon_text, $course_id) {
    // Get the course post date
    $post_date = get_post_time('U', true, $course_id);
    
	// Get the course modified date
	// Get the initial course added time from a custom meta field
	$added_time = get_post_meta($course_id, '_course_added_time', true);

	// Get the course modified time
	$modified_time = get_post_modified_time('U', true, $course_id);

	// If the course has not been previously added, set the added time and update the meta field
	if (empty($added_time)) {
		$added_time = $modified_time;
		update_post_meta($course_id, '_course_added_time', $added_time);
	}

	// Check if the course has been updated after the initial add time
	$is_updated = ($modified_time > $added_time);

	// Prepare the ribbon text based on the added or updated time
	$ribbon_text .= ' <span class="' . ($is_updated ? 'updated-badge' : 'added-badge') . '">' .
		($is_updated ? 'Updated' : 'Added') . ' ' . date('M j, Y', ($is_updated ? $modified_time : $added_time)) . '</span>';
	
	
	// Calculate the difference in days between the current date and the course post date
    $date_diff = absint((time() - $post_date) / (24 * 60 * 60));
	
	// Add the 'New' badge on the ribbon text if the course is within 21 days of the current date
	if ($date_diff <= 21) {
        $ribbon_text .= ' <span class="new-badge">New</span>';
    }

    // Return the modified ribbon text
    return $ribbon_text;
}


function thnkbe4_register_settings() {
    add_option('thnkbe4_newbadgedays_setting', ''); // Default value
    register_setting('thnkbe4_settings_group', 'thnkbe4_newbadgedays_setting');
}
add_action('admin_init', 'thnkbe4_register_settings');

function thnkbe4_options_page() {
    add_options_page('THNKbe4 Settings', 'THNKbe4', 'manage_options', 'thnkbe4-settings', 'thnkbe4_render_options_page');
}

function thnkbe4_render_options_page() {
    ?>
    <div class="wrap">
        <h1>THNKbe4 Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('thnkbe4_settings_group'); ?>
            <?php do_settings_sections('thnkbe4-settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
add_action('admin_menu', 'thnkbe4_options_page');

function thnkbe4_settings_fields() {
    add_settings_section('thnkbe4_general_section', 'General Settings', 'thnkbe4_general_section_callback', 'thnkbe4-settings');
    add_settings_field('thnkbe4_newbadgedays_setting', 'NEW badge within ', 'thnkbe4_newbadgedays_setting_callback', 'thnkbe4-settings', 'thnkbe4_general_section');
}

function thnkbe4_general_section_callback() {
    echo 'Configure general settings for your plugin.';
}

function thnkbe4_newbadgedays_setting_callback() {
    $value = get_option('thnkbe4_newbadgedays_setting');
    echo '<input type="number" name="thnkbe4_newbadgedays_setting" value="' . esc_attr($value) . '" /> days';
}

add_action('admin_init', 'thnkbe4_settings_fields');

  
