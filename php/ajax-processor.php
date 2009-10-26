<?php 
header('Content-Type: text/html; charset=UTF-8');
define('DOING_AJAX', true);
$root = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
if (file_exists($root.'/wp-load.php')) {
		// WP 2.6
		require_once($root.'/wp-load.php');
} else {
		// Before 2.6
		require_once($root.'/wp-config.php');
}
$staticrandomposts = new static_random_posts();

if (isset($_POST['number'])) {
	$number = intval($_POST['number']);
	$action = addslashes(preg_replace("/[^a-z0-9]/i", '', strip_tags($_POST['action'])));
	$name = addslashes(preg_replace("/[^_a-z0-9]/i", '', strip_tags($_POST['name'])));
	
	check_ajax_referer($action . "_" . $number);
	
	//Get the widgets
	$settings = get_option($name);
	$widget = $settings[$number];
	
	//Get the new post IDs
	$widget = $staticrandomposts->build_posts(intval($widget['postlimit']),$widget);
	$post_ids = $widget['posts'];
	
	//Save the settings
	$settings[$number] = $widget;
	update_option($name, $settings);
	
	//Let's clean up the cache
	//Update WP Super Cache if available
	if(function_exists("wp_cache_clean_cache")) {
		@wp_cache_clean_cache('wp-cache-');
	}
	
	//Build and send the response
	$response = new WP_Ajax_Response();
	$response->add( array(
					'what' => 'posts',
					'id' => $number,
					'data' => $staticrandomposts->print_posts($post_ids, false)));
	
	$response->send();
}
die('');
?>
