<?php
/* 
Plugin Name: Simple Badges
Plugin URI: http://wordpress.org/extend/plugins/simple-badges
Description: Award badges to users based on simple scenarios that you can build yourself. Includes the Custom Metabox and Fields script (https://github.com/jaredatch/Custom-Metaboxes-and-Fields-for-WordPress).
Version: 0.1.alpha-20120830
Author: Ryan Imel
Author URI: http://wpcandy.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// TODO
// Create a view for all badges, taking into account that some are hidden until they are awarded.
// Start the automatically awarded badge function.

// Kudos to https://github.com/norcross/quick-vote for inspiring me to dive a bit deeper into plugin dev.

class SimpleBadges {

	/* 
	 * Static property to hold our singleton instance
	 * @var SimpleBadges
	 */
	static $instance = false;
	 
	 
	/*
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a singleton
	 * 
	 * @return SimpleBadges
	*/
	private function __construct() {
		add_action( 'init', array( $this, 'post_types' ) );
		// to make sure the thumbnail option displays for our badge post type
		// via http://codex.wordpress.org/Function_Reference/add_theme_support
		add_theme_support( 'post-thumbnails', array( 'simplebadges_badge' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_filter( 'the_content', array( $this, 'badge_post_display' ) );
		add_action( 'init', array( $this, 'initialize_metaboxes' ), 9999 );
		add_filter( 'cmb_meta_boxes', array( $this, 'metaboxes' ) );
		add_action( 'simplebadges_before_adding', array( $this, 'badge_roaming' ), 2, 9999 );
		add_action( 'wp_head', array( $this, 'badge_users_pageload' ), 9999 );
		//add_action( 'wp_head', array( $this, 'if_user_post_count' ), 9999 );
	}
	
	
	/**
	 * If an instance exists, this returns it. If not, it creates one and 
	 * returns it.
	 *
	 * @return SimpleBadges
	 */
	 public static function getInstance() {
	 	if ( !self::$instance )
	 		self::$instance = new self;
	 	return self::$instance;
	 }
	
	
	/**
	 * Define the metabox and field configurations.
	 *
	 * @param  array $meta_boxes
	 * @return array
	 */
	function metaboxes( array $meta_boxes ) {

		// Start with an underscore to hide fields from custom fields list
		$prefix = '_simplebadges_';

		$meta_boxes[] = array(
			'id'         => 'simplebadges_meta_box',
			'title'      => 'Badge Details',
			'pages'      => array( 'simplebadges_badge', ), // Post type it's active on
			'context'    => 'normal',
			'priority'   => 'high',
			'show_names' => true, // Show field names on the left
			'fields'     => array(
				array(
					'name' => 'Badge Details',
					//'desc' => 'field description (optional)',
					'id'   => $prefix . 'badge_details',
					'type' => 'multicheck',
					'options' => array(
						'hide' => 'Hide badge from users until they win it.',
						'roaming' => 'Enable Roaming: Limit this badge to one user at a time.',
						'auto' => 'Award this badge automatically (set conditions below).',
					),
				),
				array(
					'name' => 'Award badge if&hellip;',
					//'desc' => 'field description (optional)',
					'id'   => $prefix . 'badge_conditional_partone',
					'type' => 'select',
					'options' => array(
						array( 'name' => 'User post count', 'value' => 'user_post_count', ),
						array( 'name' => 'User comment count', 'value' => 'user_comment_count', ),
						// array( 'name' => 'User registration date', 'value' => 'user_registration_date', ),
						// array( 'name' => 'User ID', 'value' => 'user_id', ),
					)
				),
				array(
					'name' => '&hellip;is&hellip;',
					//'desc' => 'field description (optional)',
					'id'   => $prefix . 'badge_conditional_parttwo',
					'type' => 'select',
					'options' => array(
						array( 'name' => 'equal to', 'value' => 'is_equal_to', ),
						array( 'name' => 'less than', 'value' => 'is_less_than', ),
						array( 'name' => 'greater than', 'value' => 'is_greater_than', ),
					)
				),
				array(
					'name' => '',
					//'desc' => 'field description (optional)',
					'id'   => $prefix . 'badge_conditional_partthree',
					'type' => 'text_small',
				),
			),
		);

		// Add other metaboxes as needed

		return $meta_boxes;
	}
	
	
	/**
	 * Initialize the metabox class.
	 */
	function initialize_metaboxes() {

		// Looks for the CMB class
		if ( ! class_exists( 'cmb_Meta_Box' ) )
			require_once 'metabox/custom-metaboxes-and-fields.php';

	}
	 
	
	/**
	 * Enqueue the javascript for the admin pages of this plugin.
	 *
	 * @static wp_enqueue_script plugins_url
	 * @global $typenow, $pagenow
	 */
	public function scripts() {
		
		global $typenow, $pagenow;
		
		if( ($pagenow == 'post-new.php' || $pagenow == 'post.php') && $typenow == 'simplebadges_badge' )
			wp_enqueue_script( 'simplebadges-admin-scripts', plugins_url( '/js/simplebadges-admin.js', __FILE__ ) , array( 'jquery' ), 0.2, true );
	
	}
	 
	 
	/**
	 * Spin up a new custom post type.
	 *
	 * @static register_post_type
	 */
	public function post_types() {
	 	
		// Badges post type
		register_post_type( 'simplebadges_badge',
			array(
	 			
				// TODO: Translations! Check http://plugins.svn.wordpress.org/wp-help/tags/0.3/wp-help.php for example.
				'labels' => array(
				
					'name' => __( 'Badges' ),
					'singular_name' => __( 'Badge' ),
					'add_new' => __( 'Add New Badge' ),
					'all_items' => __( 'Badges' ),
					'add_new_item' => __( 'Add New Badge' ),
					'edit_item' => __( 'Edit Badge' ),
					'new_item' => __( 'New Badge' ),
					'view_item' => __( 'View Badge' ),
					'search_items' => __( 'Search Badges' ),
					'not_found' => __( 'Badges not found.' ),
					'not_found_in_trash' => __( 'Badge Not Found' ),
					'parent_item_colon' => __( 'Parent Badge' ),
					'menu_name' => __( 'Badges' )
				
				),
				
				'description' => 'Provided by the Simple Badges plugin.',
				'public' => true,
				'exclude_from_search' => true,	 			
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_nav_menus' => true,
				'show_in_menu' => 'tools.php',
				
				// Note: When using 'some string' to show as a submenu of a menu page 
				// created by a plugin, this item will become the first submenu item, 
				// and replace the location of the top level link. If this isn't desired, 
				// the plugin that creates the menu page needs to set the add_action priority 
				// for admin_menu to 9 or lower. 
				// - http://codex.wordpress.org/Function_Reference/register_post_type

				'show_in_admin_bar' => false,
				'menu_position' => 80,
				// 'menu_icon' => URL,
				// TODO
				'capabilities' => array(
				// Cribbed from http://plugins.svn.wordpress.org/wp-help/tags/0.3/wp-help.php
					'publish_posts' => 'manage_options',
					'edit_posts' => 'manage_options',
					'edit_others_posts' => 'manage_options',
					'delete_posts' => 'manage_options',
					'read_private_posts' => 'manage_options',
					'edit_post' => 'manage_options',
					'delete_post' => 'manage_options',
					'read_post' => 'read'
				),
				'hierarchical' => true,
				// Thinking: child badges could assume the requirements of the parent badge.
				'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'comments', 'custom-fields' ),
				// Use the CMB plugin to set these up? Would that even work in this situation.
				'has_archive' => true,
				'rewrite' => array( 
					'slug' => 'badges',
					'with_front' => false,
					'feeds' => false
				),
				'can_export' => true,
				//'register_meta_box_cb' => array( $this, 'metabox_display' )
	 		
	 		)
	 	);
	 	
	 	// flush_rewrite_rules();
	 	// Consider doing this if public rewrites are needed. Flush only on activation, though.
	 	// See http://codex.wordpress.org/Function_Reference/register_post_type
	 	
	}
	
	
	/**
	 * Returns the small badge image size.
	 *
	 * When given a badge ID and badge dimension (square), returns
	 * the badge image formatted in HTML.
	 *
	 * @param $badge_id, $badge_dimension
	 * @return $badge_image_small
	 */	
	public function badge_thumb( $badge_id, $badge_dimension ) {
	
		// Locate the right small badge image
		if ( class_exists('MultiPostThumbnails') ) {	
			
			$badge_image_small = MultiPostThumbnails::get_the_post_thumbnail( 'simplebadges_badge', 'simplebadges-smaller', $badge_id, array( $badge_dimension, $badge_dimension ) );
		
		} else {
		
			$badge_image_small = get_the_post_thumbnail( $badge_id, array( $badge_dimension, $badge_dimension ) );
		
		}
		
		return $badge_image_small;
	
	}
	
	
	/**
	 * Returns the edit link for toggling a badge.
	 *
	 * Provided a badge ID and author ID, this function provides the toggle/edit
	 * link for that particular coupling. 
	 *
	 * @param $badge_id, $author_id, $toggle_text
	 * @return $badge_link
	 */
	public function badge_edit_link( $badge_id, $author_id, $toggle_text ) {
		
		if ( current_user_can( 'manage_options' ) ) {
					
			$badge_link_url = parse_url( $_SERVER[ 'REQUEST_URI' ], PHP_URL_PATH ) . '?badge=' . $badge_id . '&badgeuser=' . $author_id;
			//$nonce = wp_create_nonce( 'simplebadges_nonce_url' );
			$badge_link_url_verified = wp_nonce_url( $badge_link_url, 'simplebadges_nonce_url_toggle' );
			$badge_link = '<a class="badge-toggle" href="' . $badge_link_url_verified . '">' . $toggle_text . '</a>';
		
		}
		
		return $badge_link;
	
	}
	
	
	/**
	 * Permissions check for content.
	 *
	 * Only shows the passed content if the current viewing user is an admin.
	 *  
	 * @param $content
	 * @return $content, false
	 */
	public function badge_protect( $content ) {
	
		if ( current_user_can( 'manage_options' ) ) {
		
			return $content;
		
		} else {
		
			return false;
			
		}
	
	}
	
	
	/**
	 * Query all the badges.
	 *
	 * @param $ids
	 * @return array
	 */
	public function badge_query( $ids = '' ) {
			
		$args = array(
			'post_type' => 'simplebadges_badge',
			'posts_per_page' => -1,
			'post__not_in' => $ids
		);
		
		$the_query = new WP_Query( $args );

		$badge_ids = array();

		while ( $the_query->have_posts() ) : $the_query->the_post();
			$badge_ids[] = get_the_ID();
		endwhile;
			
		return $badge_ids;
		
		wp_reset_postdata();
		
	}
	
	
	/**
	 * Returns output for badges belonging to the user.
	 * 
	 * @param $user_id
	 * @return array
	 */
	public function badges_owned( $user_id ) {
		
		// Line 'em up.
		$user_badges = get_user_meta( $user_id, 'simplebadges_badges', false );
		
		$display = '';
		
		foreach ( $user_badges as $user_badge ) {
			
			$badge_post = get_post( $user_badge );
			$badge_link = $this->badge_edit_link( $user_badge, $user_id, 'x' );
			$badge_thumb = $this->badge_thumb( $user_badge, '50' );
			$display .= '<li class="badge-card"><a href="' . get_permalink( $user_badge ) . '">' . $badge_thumb . '</a><h4><a href="' . get_permalink( $user_badge ) . '">' . get_the_title( $user_badge ) . '</a></h4>' . $badge_link . '<div class="desc">' . $badge_post->post_content . '</div></li>';
			
		}
		
		return $display;
		
	}
	
	
	/**
	 * Returns output for badges NOT belonging to the user.
	 * 
	 * @param $user_id
	 * @return array
	 */
	public function badges_unowned( $user_id ) {
		
		// Line 'em up.
		$user_badges = get_user_meta( $user_id, 'simplebadges_badges', false );
		$non_badges = $this->badge_query( $user_badges );
		$display = '';
		
		foreach ( $non_badges as $non_badge ) {
			
			$badge_id = $non_badge;
			$badge_link = $this->badge_edit_link( $badge_id, $user_id, '+' );
			$badge_thumb = $this->badge_thumb( $badge_id, '30' );
			$display .= '<li class="badge-card"><a href="' . get_permalink( $badge_id ) . '">' . $badge_thumb . '</a><p><a href="' . get_permalink( $badge_id ) . '">' . get_the_title( $badge_id ) . '</a></p>' . $badge_link . '</li>';
			
		}
		
		return $display;
		
	}
	
	
	/**
	 * Display badges on the author archive page.
	 * 
	 * Accepts return true/false to enable returning the output instead of echoing it.
	 * 
	 * @param $return = false
	 * @return $output
	 */
	public function author_archive_display( $return = false ) {
		
		// This thing won't have anything to do if it's used outside of an author page.
		if ( !( is_author() ) )
			return;
		
		$this->badge_auto_process();
		
		// Get the author's slug (can't always rely on this in other ways).
		$author_slug = ( get_query_var( 'author_name' ) ) ? get_user_by( 'slug', get_query_var( 'author_name') ) : get_userdata( get_query_var( 'author') );
		
		// Pull the ID from the slug.
		$author_id = $author_slug->ID;				
		
		// Build out the display.
		$owned_list = $this->badges_owned( $author_id );
		$not_list = $this->badges_unowned( $author_id );
		$protect_list = $this->badge_protect( $not_list );
				
		$output = '<div class="simplebadges-list"><ul class="owned">' . $owned_list . '</ul><ul class="not">' . $protect_list . '</ul></div>';
						
		// Out it goes, into the world.
		if ( $return ) {
			
			return $output;
		
		} else {
			
			echo $output;
						
		}	
		
	}
	
	
	/**
	 * Toggle user ownership of badges.
	 *
	 * This occurs during the display of the author archive page.
	 * 
	 */
	public function badge_users_pageload() {
		
		// This thing won't have anything to do if it's used outside of an author page.
		if ( !( is_author() ) )
			return;
		
		//$this->if_user_post_count( 'equal', '1', '18' );
		//$this->if_user_post_count( 'less_than', '100', '16' );
		//$this->if_user_post_count( 'greater_than', '0', '15' );
		
		if ( isset( $_GET[ '_wpnonce' ] ) ) {
			wp_verify_nonce( $_GET[ '_wpnonce' ], 'simplebadges_nonce_url_toggle' );
		} else {
			return;
		}
			
		if ( current_user_can( 'manage_options' ) && $this->is_badge_switching() ) {
			
			// Set some proper variables so we can get to work
			$user_id = $_GET[ 'badgeuser' ];
			$badge_id = $_GET[ 'badge' ];
			
			// Toggle badge
			$this->badge_toggle( $badge_id, $user_id );
			
		}
			
	}
	
	
	/**
	 * Adds a badge to a user.
	 * 
	 * @param @badge_id @user_id
	 */
	private function badge_add( $badge_id, $user_id) {
		
		// Action so we can do cool stuff when this happens.
		do_action( 'simplebadges_before_adding', $badge_id, $user_id );
		
		$badges = get_user_meta( $user_id, 'simplebadges_badges', false );
		
		if ( ! in_array( $badge_id, $badges ) ) {
		
			// Updates user meta with badge id.
			add_user_meta( $user_id, 'simplebadges_badges', $badge_id, false );
		
			// Action so we can do cool stuff when this happens.
			do_action( 'simplebadges_after_adding', $badge_id, $user_id );
			
		}
		
	}
	
	
	/**
	 * Removes a badge from a user.
	 * 
	 * @param @badge_id @user_id
	 */
	private function badge_remove( $badge_id, $user_id ) {
		
		// Action so we can do cool stuff when this happens.
		do_action( 'simplebadges_before_removing', $badge_id, $user_id );
		
		// Updates user meta with badge id.
		delete_user_meta( $user_id, 'simplebadges_badges', $badge_id );
			
		// Action so we can do cool stuff when this happens.
		do_action( 'simplebadges_after_removing', $badge_id, $user_id );
		
	}
	
	
	/**
	 * Toggles a badge from a user.
	 * 
	 * Perhaps you don't know whether a user has a badge or not. This 
	 * function will toggle it -- if it has it, will remove it, if not, 
	 * will add it.
	 * 
	 * @param @badge_id @user_id
	 */
	private function badge_toggle( $badge_id, $user_id ) {
		
		// Grab the user's badges.
		$badges = get_user_meta( $user_id, 'simplebadges_badges', false );
		
		if ( in_array( $badge_id, $badges ) ) {
				
			// Let's toggle and remove the badge
			$this->badge_remove( $badge_id, $user_id );
									
		} else {
				
			// Toggle and add the badge
			$this->badge_add( $badge_id, $user_id );
				
		}
		
	}
	
	
	/**
	 * Checks for a roaming badge, if so removes everyone the badge from others.
	 * 
	 * @param $badge_id @user_id
	 */
	public function badge_roaming( $badge_id, $user_id ) {
		
		// Save the badge meta value as a variable
		$values = get_post_custom_values( '_simplebadges_badge_details', $badge_id );
		$roaming = 'roaming';
		
		// If the value = roaming, or if roaming is on
		if ( ( $values ) && ( in_array ( $roaming, $values ) ) ) {
			
			$users = get_users();

			foreach ($users as $user) {

				$id = $user->ID;
				// Let's toggle and remove the badge
				$this->badge_remove( $badge_id, $id );

			}
			
		}
		
	}
	
	
	/**
	 * Pull up meta for each badge.
	 * 
	 * @param
	 * @return $meta
	 */
	function meta_query() {
		
		$badges = $this->badge_query();
		
		$badge_meta = array();
		
		foreach ( $badges as $badge ) {
			
			$values = get_post_custom_values( '_simplebadges_badge_details', $badge );
			
			if ( is_array( $values ) && in_array( 'auto', $values ) ) {
				
				$conditional = get_post_meta( $badge, '_simplebadges_badge_conditional_partone', true );
				$argument = get_post_meta( $badge, '_simplebadges_badge_conditional_parttwo', true );
				$value = get_post_meta( $badge, '_simplebadges_badge_conditional_partthree',  true );
				
				$meta[] = array(
					"badge"			=> $badge,
					"conditional" 	=> $conditional,
					"argument" 		=> $argument,
					"value" 		=> $value
				);
				
				// For testing, remove any time.
				//print_r( $meta );
				
			}
			
		}
		
		return $meta;
		
	}
	
	
	/**
	 * Run through badge meta and run conditional functions where appropriate.
	 * 
	 * @param
	 */
	function badge_auto_process() {
		
		$metas = $this->meta_query();
		
		//print_r( $metas );
		
		if ( is_array( $metas ) ) {
			foreach ( $metas as $meta ) {

				$badge_id = $meta[ 'badge' ];
				$conditional = $meta[ 'conditional' ];
				$argument = $meta[ 'argument' ];
				$value = $meta[ 'value' ];

				switch ( $conditional ) {

					case 'user_post_count':

						$this->if_user_post_count( $argument, $value, $badge_id );

						break;

					case 'user_comment_count':

						$this->if_user_comment_count( $argument, $value, $badge_id );

						break;
				}


			}	
		}
		
	}
	
	
	/**
	 * Award badges based on user post count.
	 * 
	 * 
	 * @param $argument $value $badge_id
	 */
	public function if_user_post_count( $argument, $value, $badge_id ) {
		
		if ( $this->is_badge_switching() )
			return;
		
		$users = get_users();
						
		foreach ( $users as $user ) {
				
			$user_id = $user->ID;
			$count = count_user_posts( $user_id );
			
			switch ( $argument ) {
			
				case 'is_equal_to':
				
					if ( $count == $value ) {

						$this->badge_add( $badge_id, $user_id );

					}
				
					break;
					
				case 'is_less_than':
				
					if ( $count < $value ) {

						$this->badge_add( $badge_id, $user_id );

					}
				
					break;
					
				case 'is_greater_than':
				
					if ( $count > $value ) {

						$this->badge_add( $badge_id, $user_id );

					}
				
					break;
			}
		}
							
	}
	
	
	/**
	 * Pull a registered user's comment count based on their id.
	 * 
	 * @param $user_id
	 * @return $comment_count
	 */
	public function get_user_comment_count( $user_id ) {
		
		global $wpdb;
		$email = get_the_author_meta( 'user_email', $user_id );
		
		$comment_count = $wpdb->get_var('SELECT COUNT(comment_ID) FROM ' . $wpdb->comments. ' WHERE comment_author_email = "' . $email . '"');
		
		return $comment_count;
		
	}
	
	
	/**
	 * Award badges based on user comment count.
	 * 
	 * 
	 * @param $argument $value $badge_id
	 */
	public function if_user_comment_count( $argument, $value, $badge_id ) {
		
		if ( $this->is_badge_switching() )
			return;
		
		$users = get_users();
						
		foreach ( $users as $user ) {
				
			$user_id = $user->ID;
			
			$count = $this->get_user_comment_count( $user_id );

			switch ( $argument ) {
			
				case 'is_equal_to':
				
					if ( $count == $value ) {
						
						$this->badge_add( $badge_id, $user_id );

					}
				
					break;
					
				case 'is_less_than':
				
					if ( $count < $value ) {

						$this->badge_add( $badge_id, $user_id );

					}
				
					break;
					
				case 'is_greater_than':
				
					if ( $count > $value ) {

						$this->badge_add( $badge_id, $user_id );

					}
				
					break;
			}
		}
							
	}
	
	
	/**
	 * Conditional that checks whether badge toggling is taking place or not.
	 *
	 * @return true or false
	 */
	public function is_badge_switching() {
	
		if ( isset( $_GET[ 'badgeuser' ] ) || isset( $_GET[ 'badge' ] ) ) {
			
			return true;
			
		}
		
			return false;
		
	}
	
	
	/**
	 * Filter the display of badges.
	 *
	 * Adds badges to the output on badge archive pages. Filters the $content and 
	 * first displays the $badge_image, then the $content, then $badge_winners.
	 * 
	 * @return 
	 */
	public function badge_post_display( $content ) {
		
		if ( !( is_post_type_archive( 'simplebadges_badge' ) || ( is_single() && ( 'simplebadges_badge' == get_post_type() ) )  ) )
			return $content;
			
		$badge_id = get_the_ID();
		
		$badge_image = '<div style="float:right;">' . get_the_post_thumbnail( $badge_id, array(50,50) ) . '</div>';
		
		$blogusers = get_users();
		
		foreach ($blogusers as $bloguser) {
			
			$id = $bloguser->ID;
			$user_badges = get_user_meta( $id, 'simplebadges_badges', false );
			$badge_winners = '';
			
			if ( in_array( $badge_id, $user_badges ) ) {
				$badge_winners .= '<a href="' . get_author_posts_url( $id ) . '">' . get_avatar( $id, 30 ) . '</a>';
			}	
			
		}
		
		return $badge_image . $content . $badge_winners;
		
	}
		

// end class
}

// Instantiate our class
$SimpleBadges = SimpleBadges::getInstance();


// Simple Badges display function
function simplebadges_user() {
	global $SimpleBadges;
	
	if ( $SimpleBadges ) {
		return $SimpleBadges->author_archive_display();
	}
}
