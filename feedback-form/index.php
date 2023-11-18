<?php
/*
Plugin Name: Was This Article Helpful?
Description: Simple article feedback plugin.
Version: 1.0.0
Author: Rintu
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


function start_session()
{
	if (!isset($_SESSION) ) {
		session_start();
	}
}
add_action('init', 'start_session', 1);

// Installation plugin
function wp_feedback_activate()
{

	// Add default options
	add_option('wp_feedback_types', '["post","page"]');
	add_option('wp_feedback_question_text', 'Was this article helpful?');
	add_option('wp_feedback_yes_text', 'Yes');
	add_option('wp_feedback_no_text', 'No');
	add_option('wp_feedback_thank_text', 'Thanks for your feedback!');
	wp_feedback_create_table();
}

register_activation_hook(__FILE__, 'wp_feedback_activate');

function wp_feedback_create_table()
{
	global $wpdb;
	global $cg_db_version;

	$table_name = $wpdb->prefix . 'wp_was_this_helpful';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
        EmailAddress VARCHAR(100) NOT NULL,
		UserType VARCHAR(60) NOT NULL,
		Brand text,
        ArticleURL text,
        Categories text,
		Tags text,
		feedback text,
        Timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);

	add_option('cg_db_version', $cg_db_version);
}


// unistallation plugin
function wp_feedback_uninstall()
{

	// delete options
	delete_option('wp_feedback_types');
	delete_option('wp_feedback_question_text');
	delete_option('wp_feedback_yes_text');
	delete_option('wp_feedback_no_text');
	delete_option('wp_feedback_thank_text');

	// Delete custom fields
	global $wpdb;
	$table = $wpdb->prefix . 'postmeta';
	$wpdb->delete($table, array('meta_key' => '_wp_feedback_no'));
	$wpdb->delete($table, array('meta_key' => '_wp_feedback_yes'));

}
register_uninstall_hook(__FILE__, 'wp_feedback_uninstall');

add_action('rest_api_init', function () {
	register_rest_route('was_this_helpful/v1', '/feedback', array(
		'methods' => 'GET',
		'callback' => 'wp_get_user_feedback',
		'permission_callback' => '__return_true', /*function () {
		   return current_user_can( 'edit_others_posts' );
		   }*/
	));
});

function wp_get_user_feedback($data)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'wp_was_this_helpful';

	$query = "SELECT * FROM " . $table_name . " ORDER BY id DESC";
	$list = $wpdb->get_results($query);
	return $list;
}


// Adds "was this helpful" after the content
function wp_feedback_after_post_content($content)
{
	// Read selected post types

	$selected_post_types = json_decode(get_option("wp_feedback_types"));
	// show on only selected post types
	if (is_singular($selected_post_types)) {
		// Get post id
		$post_id = get_the_ID();

		//$cookie_val = $_COOKIE["feedback_id_".$post_id];
		// Dont show if already voted
		$session_name = 'cg_lead_info';
		$user_email = '';
		if (isset($_SESSION[$session_name]) && $_SESSION[$session_name]['wp_cg_logged_in'] == true) {
			$user_email = $_SESSION[$session_name]['wp_cg_user_email'];
		}

		if (!isset($_COOKIE["feedback_id_" . $post_id]) || $_COOKIE["_feedback_username"] != $user_email) {
			$content .= '<div id="was_this_helpful" data-post-id="' . $post_id . '" data-thank-text="' . get_option("wp_feedback_thank_text") . '"><div id="wp_feedback_title">' . get_option("wp_feedback_question_text") . '</div><div id="wp_feedback_yes_no"><span class="feedback_yes" data-value="1">' . get_option("wp_feedback_yes_text") . '</span><span class="feedback_no" data-value="0">' . get_option("wp_feedback_no_text") . '</span></div></div>';

		}

	}

	return $content;

}

add_filter("the_content", "wp_feedback_after_post_content", 100);

function my_acf_load_value($value, $post_id, $field)
{
	// Read selected post types
	$selected_post_types = json_decode(get_option("wp_feedback_types"));
	// show on only selected post types
	if (is_singular($selected_post_types)) {
		// Get post id

		//$cookie_val = $_COOKIE["feedback_id_".$post_id];
		// Dont show if already voted
		$session_name = 'cg_lead_info';
		$user_email = '';
		if (isset($_SESSION[$session_name]) && $_SESSION[$session_name]['wp_cg_logged_in'] == true) {
			$user_email = $_SESSION[$session_name]['wp_cg_user_email'];

		}

		$array_aize = sizeof($value, 0);
		if (!isset($_COOKIE["feedback_id_" . $post_id]) || $_COOKIE["_feedback_username"] != $user_email) {
			for ($i = 0; $i < $array_aize; $i++) {
				if (array_key_exists("field_59dca20d3e19b", $value[$i])) {
					$value[$i]['field_59dca20d3e19b'] .= '<div id="was_this_helpful" data-post-id="' . $post_id . '" data-thank-text="' . get_option("wp_feedback_thank_text") . '"><div id="wp_feedback_title">' . get_option("wp_feedback_question_text") . '</div><div id="wp_feedback_yes_no"><span class="feedback_yes" data-value="1">' . get_option("wp_feedback_yes_text") . '</span><span class="feedback_no" data-value="0">' . get_option("wp_feedback_no_text") . '</span></div></div>';
					break;
				}
			}
		}
	}
	return $value;
}


add_filter('acf/load_value/name=reports', 'my_acf_load_value', '', 100);


// Adds script and styles
function wp_feedback_style_scripts()
{
	if (!isset($_SESSION)) {
		session_start();
	}
	// Read selected post types
	$selected_post_types = json_decode(get_option("wp_feedback_types"));
	$user_email = '';
	$session_name = 'cg_lead_info';
	//var_dump($_SESSION);
	if (isset($_SESSION[$session_name]) && $_SESSION[$session_name]['wp_cg_logged_in'] == true) {
		$user_email = $_SESSION[$session_name]['wp_cg_user_email'];
	}

	// show on only selected post types
	if (is_singular($selected_post_types)) {

		wp_enqueue_style('wp_feedback-style', plugins_url('/css/style.css', __FILE__));
		wp_enqueue_script('wp_feedback-script', plugins_url('/js/script.js', __FILE__), array('jquery'), '1.0', TRUE);
		wp_localize_script('wp_feedback-script', 'myAjax', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce_wp_feedback' => wp_create_nonce("wp_feedback_nonce"),
			'post_id' => get_the_id(),
			'user_email' => $user_email
		)
		);
	}

}

add_action('wp_enqueue_scripts', 'wp_feedback_style_scripts', 100);



// Ajax callback for yes-no
function wp_feedback_ajax_callback()
{

	// Check Nonce
	/*if(!wp_verify_nonce($_REQUEST['nonce'], "wp_feedback_nonce")) {
	exit("No naughty business please.");
	}*/

	// Get posts
	$post_id = intval($_REQUEST['id']);
	$url = $_REQUEST['url'];
	$value = intval($_REQUEST['val']);
	$feedback = 'Yes';
	if ($value == 0)
		$feedback = 'No';
	$user_type = 'Anonymous';
	$session_name = 'cg_lead_info';
	$user_email = '';
	var_dump($_SESSION);
	if (isset($_SESSION[$session_name]) && $_SESSION[$session_name]['wp_cg_logged_in'] == true) {
	if( $_SESSION[$session_name]['wp_cg_user_email'])
		$user_email = $_SESSION[$session_name]['wp_cg_user_email'];
		 $user_type = 'Logged In';
		setcookie('_feedback_username', $user_email, time() + (86400 * 30), "/"); // 86400 = 1 day
		setcookie('feedback_id_' . $post_id, 1, time() + (86400 * 30), "/");
	}

	$categories = get_the_category($post_id); //$post->ID
	$output_category = array_map(function ($object) {
		return $object->name; }, $categories);
	$post_categories = implode(', ', $output_category);

	$tags = get_the_tags($post_id);
	$output_tags = array_map(function ($object) {
		return $object->name; }, $tags);
	$post_tags = implode(', ', $output_tags);

	global $wpdb;
	$table_name = $wpdb->prefix . 'wp_was_this_helpful';
	$feedback_inserted = $wpdb->insert(
		$table_name,
		array(
			'EmailAddress' => $user_email,
			'UserType' => $user_type,
			'Brand' => $_SERVER['SERVER_NAME'],
			'ArticleURL' => $url,
			'Categories' => $post_categories,
			'Tags' => $post_tags,
			'feedback' => $feedback,
			'Timestamp' => current_time('mysql'),

		)
	);
	echo $feedback_inserted;

	// $value_name = "_wp_feedback_no";
	// if($value == "1"){
	// 	$value_name = "_wp_feedback_yes";
	// }
	// // Cookie check
	// if(isset($_COOKIE["feedback_id_".$post_id])){
	// 	exit("No naughty business please.");
	// }
	// // Get 
	// $current_post_value = get_post_meta($post_id, $value_name, true);
	// // Make it zero if empty
	// if(empty($current_post_value)){
	// 	$current_post_value = 0;
	// }
	// // Update value
	// $new_value = $current_post_value + 1;
	// // Update post meta
	// update_post_meta($post_id, $value_name, $new_value);


	// Die WP
	wp_die();

}

add_action("wp_ajax_wp_feedback_ajax", "wp_feedback_ajax_callback");
add_action("wp_ajax_nopriv_wp_feedback_ajax", "wp_feedback_ajax_callback");


// Register option page
function wp_feedback_register_options_page()
{
	add_options_page('Helpful Plugin Options', 'Was this Helpful?', 'manage_options', 'wp_feedback', 'wp_feedback_options_page');
}

add_action('admin_menu', 'wp_feedback_register_options_page');



// Option page settings
function wp_feedback_options_page()
{

	// If isset
	if (isset($_POST['wp_feedback_options_nonce'])) {

		// Check Nonce
		if (wp_verify_nonce($_POST['wp_feedback_options_nonce'], "wp_feedback_options_nonce")) {

			// Update options
			update_option('wp_feedback_types', json_encode(array_values($_POST['wp_feedback_types'])));
			update_option('wp_feedback_question_text', sanitize_text_field($_POST["wp_feedback_question_text"]));
			update_option('wp_feedback_yes_text', sanitize_text_field($_POST["wp_feedback_yes_text"]));
			update_option('wp_feedback_no_text', sanitize_text_field($_POST["wp_feedback_no_text"]));
			update_option('wp_feedback_thank_text', sanitize_text_field($_POST["wp_feedback_thank_text"]));

			// Settings saved
			echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>Settings saved.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';

		}
	}

	?>
	<div class="wrap">

		<h2>Helpful Options</h2>

		<p>"Was this article helpful" widget will automatically appear at the end of the articles. Please select the post
			types that you want to show this widget.</p>
		<form method="post" action="options-general.php?page=wp_feedback">

			<input type="hidden" value="<?php echo wp_create_nonce("wp_feedback_options_nonce"); ?>"
				name="wp_feedback_options_nonce" />

			<table class="form-table">

				<tr>
					<th scope="row"><label for="wp_feedback_post_types">Post Types</label></th>
					<td>
						<?php

						// Post Types
						$post_types = get_post_types(array('public' => true), 'names');
						$selected_post_types = get_option("wp_feedback_types");

						// Read selected post types
						$selected_type_array = json_decode($selected_post_types);

						// Foreach
						foreach ($post_types as $post_type) {

							// Skip Attachment
							if ($post_type == 'attachment' || $post_type == 'cnt-newsletter') {
								continue;
							}

							// Get value
							$checkbox = '';
							if (!empty($selected_type_array)) {
								if (in_array($post_type, $selected_type_array)) {
									$checkbox = ' checked';
								}
							}

							// print inputs
							echo '<label for="' . $post_type . '" style="margin-right:18px;"><input' . $checkbox . ' name="wp_feedback_types[]" type="checkbox" id="' . $post_type . '" value="' . $post_type . '">' . $post_type . '</label>';

						}

						?>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="wp_feedback_question_text">Question</label></th>
					<td><input type="text" placeholder="Was this article helpful?" class="regular-text"
							id="wp_feedback_question_text" name="wp_feedback_question_text"
							value="<?php echo get_option('wp_feedback_question_text'); ?>" /></td>
				</tr>

				<tr>
				<tr>
					<th scope="row"><label for="wp_feedback_yes_text">Positive Answer</label></th>
					<td><input type="text" placeholder="Yes" class="regular-text" id="wp_feedback_yes_text"
							name="wp_feedback_yes_text" value="<?php echo get_option('wp_feedback_yes_text'); ?>" /></td>
				</tr>

				<tr>
					<th scope="row"><label for="wp_feedback_no_text">Negative Answer</label></th>
					<td><input type="text" placeholder="No" class="regular-text" id="wp_feedback_no_text"
							name="wp_feedback_no_text" value="<?php echo get_option('wp_feedback_no_text'); ?>" /></td>
				</tr>

				<tr>
					<th scope="row"><label for="wp_feedback_thank_text">Thank You Message</label></th>
					<td><input type="text" placeholder="Thanks for your feedback!" class="regular-text"
							id="wp_feedback_thank_text" name="wp_feedback_thank_text"
							value="<?php echo get_option('wp_feedback_thank_text'); ?>" /></td>
				</tr>

			</table>


			<?php submit_button(); ?>

		</form>

	</div>
	<?php

}
