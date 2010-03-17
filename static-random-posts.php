<?php
/*
Plugin Name: Static Random Posts Widget
Plugin URI: http://www.ronalfy.com/2009/10/26/wordpress-static-random-post
Description: This plugin allows the display of random posts, but allows the user to determine how often the random posts are refreshed. 
Author: Ronald Huereca
Version: 1.1
Requires at least: 2.9.2
Author URI: http://www.ronalfy.com/
Some code borrowed from Advanced Random Posts - http://www.yakupgovler.com/?p=416
*/ 

if (!class_exists('static_random_posts')) {
    class static_random_posts	extends WP_Widget {		
			var $localizationName = "staticRandom";
			var $adminOptionsName = "static-random-posts";
			/**
			* PHP 4 Compatible Constructor
			*/
			function static_random_posts(){
				$this->adminOptions = $this->get_admin_options();
				if ( !defined('WP_CONTENT_URL') )
					define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
				if ( !defined('WP_CONTENT_DIR'))
					define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
				
				//Initialization stuff
				add_action('init', array(&$this, 'init'));
				
				//Admin options
				add_action('admin_menu', array(&$this,'add_admin_pages'));
				//JavaScript
				add_action('wp_print_scripts', array(&$this,'add_post_scripts'),1000);
				
				//Widget stuff
				$widget_ops = array('description' => __('Shows Static Random Posts.', $this->localizationName) );
				//Create widget
				$this->WP_Widget('staticrandomposts', __('Static Random Posts', $this->localizationName), $widget_ops);
			}
			
		
			
			/* init - Run upon WordPress initialization */
			function init() {
				//* Begin Localization Code */
				$static_random_posts_locale = get_locale();
				$static_random_posts_mofile = WP_CONTENT_DIR . "/plugins/static-random-posts/languages/" . $this->localizationName . "-". $static_random_posts_locale.".mo";
				load_textdomain($this->localizationName, $static_random_posts_mofile);
			//* End Localization Code */
			}//end function init
						
						
			// widget - Displays the widget
			function widget($args, $instance) {
				extract($args, EXTR_SKIP);
				echo $before_widget;
				$title = empty($instance['title']) ? __('Random Posts', $this->localizationName) : apply_filters('widget_title', $instance['title']);
				
				if ( !empty( $title ) ) {
					echo $before_title . $title . $after_title;
				};
				//Get posts
				$post_ids = $this->get_posts($instance);
				if (!empty($post_ids)) {
					echo "<ul class='static-random-posts' id='static-random-posts-$this->number'>";
					$this->print_posts($post_ids);
					echo "</ul>";
					if (current_user_can('edit_users')) {
						$refresh_url = clean_url( wp_nonce_url(get_bloginfo('url') . "/?SRP=ajax-processor&action=refreshstatic&number=$this->number&name=$this->option_name", "refreshstatic_$this->number"));
						echo "<br /><a href='$refresh_url' class='static-refresh'>" . __("Refresh...",$this->localizationName) . "</a>";
					}
				}
				echo $after_widget;
			}
			
			//Prints or returns the LI structure of the posts
			function print_posts($post_ids,$echo = true) {
				if (empty($post_ids)) { return ''; }
				$posts = get_posts("include=$post_ids");
				$posts_string = '';
				foreach ($posts as $post) {
					$posts_string .= "<li><a href='" . get_permalink($post->ID) . "' title='". $post_title ."'>" . htmlspecialchars(stripslashes(strip_tags($post->post_title))) ."</a></li>\n";
				}
				if ($echo) {
					echo $posts_string;
				} else {
					return $posts_string;
				}
			}
			
			//Returns the post IDs of the posts to retrieve
			function get_posts($instance, $build = false) {
				//Get post limit
				$limit = intval($instance['postlimit']);
				
				$all_instances = $this->get_settings();
				//If no posts, add posts and a time
				if (empty($instance['posts'])) {
					//Build the new posts
					$instance = $this->build_posts($limit,$instance);
					$all_instances[$this->number] = $instance;
					update_option( $this->option_name, $all_instances );
				}  elseif(($instance['time']-time()) <=0) {
					//Check to see if the time has expired
					//Rebuild posts
					$instance = $this->build_posts($limit,$instance);
					$all_instances[$this->number] = $instance;
					update_option( $this->option_name, $all_instances );
				} elseif ($build == true) {
					//Build for the heck of it
					$instance = $this->build_posts($limit,$instance);
					$all_instances[$this->number] = $instance;
					update_option( $this->option_name, $all_instances );
				}
				if (empty($instance['posts'])) {
					$instance['posts'] = '';
				}
				return $instance['posts'];
			}
			
			//Builds and saves posts for the widget
			function build_posts($limit, $instance) {
				//Get categories to exclude
				$cats = @implode(',', $this->adminOptions['categories']);
				
				$posts = get_posts("cat=$cats&showposts=$limit&orderby=rand"); //get posts by random
				$post_ids = array();
				for ($i=0; $i<$limit; $i++) {
					$post_ids[$i] = $posts[$i]->ID;
				}
				$post_ids = implode(',', $post_ids);
				$instance['posts'] = $post_ids;
				$instance['time'] = time()+(60*intval($this->adminOptions['minutes']));
				
				return $instance;
			}
			
			//Updates widget options
			function update($new, $old) {
				$instance = $old;
				$instance['postlimit'] = intval($new['postlimit']);
				$instance['title'] = esc_attr($new['title']);
				return $instance;
			}
						
			//Widget form
			function form($instance) {
				$instance = wp_parse_args((array)$instance, array('title'=> __("Random Posts", $this->localizationName),'postlimit'=>5,'posts'=>'', 'time'=>''));
				$postlimit = intval($instance['postlimit']);
				$posts = $instance['posts'];
				$title = esc_attr($instance['title']);
				?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e("Title", $this->localizationName); ?><input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('postlimit'); ?>"><?php _e("Number of Posts to Show", $this->localizationName); ?><input class="widefat" id="<?php echo $this->get_field_id('postlimit'); ?>" name="<?php echo $this->get_field_name('postlimit'); ?>" type="text" value="<?php echo $postlimit; ?>" />
				</label>
			</p>
			<p><?php _e("Please visit",$this->localizationName)?> <a href="options-general.php?page=static-random-posts.php"><?php _e("Static Random Posts",$this->localizationName)?></a> <?php _e("to adjust the global settings",$this->localizationName)?>.</p>
			<?php
			}//End function form
						/*BEGIN UTILITY FUNCTIONS - Grouped by function and not by name */
			function add_admin_pages(){
					add_options_page('Static Random Posts', 'Static Random Posts', 9, basename(__FILE__), array(&$this, 'print_admin_page'));
			}
			//Provides the interface for the admin pages
			function print_admin_page() {
				include dirname(__FILE__) . '/php/admin-panel.php';
			}
			//Returns an array of admin options
			function get_admin_options() {
				if (empty($this->adminOptions)) {
					$adminOptions = array(
						'minutes' => '5',
						'categories' => ''
					);
					$options = get_option($this->adminOptionsName);
					if (!empty($options)) {
						foreach ($options as $key => $option)
									if (array_key_exists($key, $adminOptions)) {
										$adminOptions[$key] = $option;
									}
					}
					$this->adminOptions = $adminOptions;
					$this->save_admin_options();								
				}
				return $this->adminOptions;
			}
			//Saves for admin 
			function save_admin_options(){
				if (!empty($this->adminOptions)) {
					update_option($this->adminOptionsName, $this->adminOptions);
				}
			}
						//Add scripts to the front-end of the blog
			function add_post_scripts() {
				if (is_active_widget(true, $this->id, $this->id_base) == false) { return; }
				wp_enqueue_script("wp-ajax-response");
				wp_enqueue_script('static_random_posts_script', plugins_url('static-random-posts') . '/js/static-random-posts.js', array("jquery", "wp-ajax-response") , 1.0);
				wp_localize_script( 'static_random_posts_script', 'staticrandomposts', $this->get_js_vars());
			}
			//Echoes out various JavaScript vars needed for the scripts
			function get_js_vars() {
				return array(
					'SRP_Loading' => __('Loading...', $this->localizationName),
					'SRP_Refresh' => __('Refresh...', $this->localizationName),
					'SRP_SiteUrl' =>  get_bloginfo('url')
				);
			} //end get_js_vars
			/*END UTILITY FUNCTIONS*/
    }//End class
}
function SRP_load_page() {
	$pagepath = WP_PLUGIN_DIR . '/static-random-posts/php/';
	switch(get_query_var('SRP')) {
		case 'ajax-processor':
			include($pagepath . 'ajax-processor.php');
			exit;
		default:
			break;
	}
} //end function load_page
function SRP_query_trigger($queries) {
	array_push($queries, 'SRP');
	return $queries;
}//end function query_trigger
add_action('template_redirect', 'SRP_load_page');
add_filter('query_vars', 'SRP_query_trigger');
add_action('widgets_init', create_function('', 'return register_widget("static_random_posts");') );


?>