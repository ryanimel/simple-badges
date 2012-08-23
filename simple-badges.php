<?php
/* 
Plugin Name: Simple Badges
Plugin URI: http://wordpress.org/extend/plugins/simple-badges
Description: Award badges to users based on simple scenarios that you can build yourself.
Version: 0.1
Author: Ryan Imel
Author URI: http://wpcandy.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


// sb_rwi_namespace
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
		add_action( 'admin_menu', array( $this, 'metabox_add' ) );
		add_action( 'save_post', array( $this, 'metabox_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_filter( 'the_content', array( $this, 'badge_post_display' ) );
	}
	
	
	/*
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
	 
	
	/*
	 * Javascript for this plugin.
	 *
	 */
	 
	public function scripts() {
		global $typenow, $pagenow;
		if( ($pagenow == 'post-new.php' || $pagenow == 'post.php') && $typenow == 'simplebadges_badge' )
		
		wp_enqueue_script( 'simplebadges-admin-scripts', plugins_url( '/js/simplebadges-admin.js', __FILE__ ) , array( 'jquery' ), 0.2, true );
	
	}
	 
	 
	 /*
	  * Spin up a new custom post type.
	  *
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
	 
	 
	 /*
	  * Metabox fields
	  * 
	  */
		
		public $metabox_fields = array(
		
			'id' => 'simplebadges-meta-box',
			'title' => 'Simple Badges',
			'page' => 'simplebadges_badge',
			'context' => 'normal',
			'priority' => 'high',
			'fields' => array(
				array(
					'name' => 'Badge Type',
					'desc' => '',
					'id' => 'simplebadges_badge_type',
					'type' => 'radio',
					'options' => array( 
						array(
							'value' => 'Award this page manually.',
							'id' => 'simplebadges_badge_type_manual',
							'name' => 'group1'
						),
						array(
							'value' => 'Award this badge automatically.',
							'id' => 'simplebadges_badge_type_auto',
							'name' => 'group1'
						)
					),
					'std' => 'Award this page manually.'
				),
				array(
					'name' => 'Hidden Badges',
					'desc' => 'Hide this badge from users.',
					'id' => 'simplebadges_badge_hidetoggle',
					'type' => 'checkbox',
					'std' => 'off'
				),
				array(
					'name' => 'Award this badge if&hellip;',
					'desc' => '',
					'id' => 'simplebadges_badge_conditional_partone',
					'type' => 'select',
					'options' => array( 'User post count', 'User comment count', 'User registration date', 'User ID' ),
					'std' => ''
				),
				array(
					'name' => 'Conditional part two&hellip;',
					'desc' => '',
					'id' => 'simplebadges_badge_conditional_parttwo',
					'type' => 'select',
					'options' => array( 'is equal to', 'is less than', 'is greater than' ),
					'std' => ''
				),
				array(
					'name' => 'Conditional part three&hellip;',
					'desc' => '',
					'id' => 'simplebadges_badge_conditional_partthree',
					'type' => 'text'
				)
			)
		
		);
	
	
	
	/*
	 * Add meta box 
	 * 
	 */
	
	public function metabox_add() {
			
		add_meta_box( $this->metabox_fields['id'], $this->metabox_fields['title'], array( $this, 'metabox_display' ), $this->metabox_fields['page'], $this->metabox_fields['context'], $this->metabox_fields['priority'] );
	
	}
	
	
	/*
	 * Display meta box
	 *
	 */  
	
	public function metabox_display() {
	
		global $post;
		
		echo '<input type="hidden" name="simplefields_metabox_nonce" value="', wp_create_nonce( basename(__FILE__)), '" />';
		echo '<table class="form-table">';
		
		foreach ( $this->metabox_fields['fields'] as $field ) {
		
			$meta = get_post_meta( $post->ID, $field['id'], true );
			
			echo '<tr>',
				'<th style="width:20%">',
					'<label for="', $field['id'], '">', esc_attr( $field['name'] ), '</label>',
				'</th>',
				'<td>';
				
			// Depending on what inputs are dropped into our box
			switch ( $field['type'] ) {
			
				case 'text' : 
					echo '<input type="text" name"', $field['id'], '" id="', $field['id'], '" value="';
					echo esc_attr( $meta );
					echo '" size="30" style="width: 12%" />', '<br />', $field['desc'];
					break;
					
				case 'textarea' :
					echo '<textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="4" style="width: 97%">', $meta ? $meta : $field['std'], '</textarea>', '<br />', $field['desc'];
					break;
					
				case 'select' : 
					echo '<select name="', $field['id'], '" id="', $field['id'], '">';
					
					foreach ( $field['options'] as $option ) {
						echo '<option', $meta == $option ? ' selected="selected"' : '', '>', $option, '</option>';
					}
					echo '</select>';
					break;
					
				case 'radio' :
					foreach ( $field['options'] as $option ) {
					
					$checkedvalue = '';
					if ( ( !($meta) && $field['std'] == $option['value'] ) || $meta == $option['value'] ) {
						$checkedvalue .= ' checked="checked"';
					}
					
						echo '<input type="radio" name="', $field['id'], '" id="', $option['value'], '" value="', $option['value'], '"', $checkedvalue, ' />&nbsp;<label for="', $option['value'], '">', $option['value'], '</label><br />';
					}
					break;
					
				case 'checkbox' :
					echo '<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />';
					echo '<label for="', $field['id'], '" id="', $field['id'], '-label">&nbsp;', $field['desc'], '</label>';
					break;
			
			}
			
			echo '</td></tr>';

		}
		
		echo '</table>';
	
	}
	
	
	/*
	 * Save the metabox
	 *
	 */
	 
	public function metabox_save( $post_id ) {
		
		if ( !wp_verify_nonce( $_POST[ 'simplefields_metabox_nonce' ], basename(__FILE__) ) ) {
			return $post_id;
		}
		
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		if ( 'page' == $_POST['post_type'] ) {
			if( !current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		
		foreach ( $this->metabox_fields['fields'] as $field ) {
			$old = get_post_meta( $post_id, $field['id'], true );
			$new = $_POST[$field['id']];
			if ( $new && $new != $old ) {
				update_post_meta( $post_id, $field['id'], $new );
			} elseif ( '' == $new && $old ) {
				delete_post_meta( $post_id, $field['id'], $old );
			}
			
		}
		
	}
	
	
	
	public function badge_thumb( $badge_id, $badge_dimension ) {
	
		// Locate the right small badge image
		if ( class_exists('MultiPostThumbnails') ) {	
			$badge_image_small = MultiPostThumbnails::get_the_post_thumbnail( 'simplebadges_badge', 'simplebadges-smaller', get_the_ID(), array( $badge_dimension, $badge_dimension ) );
		} else {
			$badge_image_small = get_the_post_thumbnail( $badge_id, array( $badge_dimension, $badge_dimension ) );
		}
		
		return $badge_image_small;
	
	}
	
	
	public function badge_edit_link( $badge_id, $author_id, $toggle_text ) {
	
		if ( current_user_can( 'manage_options' ) ) {
					
			$badge_link_url = parse_url( $_SERVER[ 'REQUEST_URI' ],PHP_URL_PATH ) . '?badge=' . $badge_id . '&badgeuser=' . $author_id;
			$badge_link_url_verified = wp_nonce_url( $badge_link_url, 'simplebadges_nonce_url' );
			$badge_link = '<a class="badge-toggle" href="' . $badge_link_url_verified . '">' . $toggle_text . '</a>';
			
		}
		
		return $badge_link;
	
	}
	
	
	public function badge_protect( $content ) {
	
		if ( current_user_can( 'manage_options' ) ) {
		
			return $content;
		
		} else {
		
			return false;
			
		}
	
	}
	
	
	/*
	 * Display badges on the author archive page
	 * Accepts return true/false to enable returning the output instead of echoing it.
	 */
	
	public function author_archive_display( $return = false ) {
		
		// This thing won't have anything to do if it's used outside of an author page.
		if ( !( is_author() ) )
			return;
		
		$this->badge_users_update();
		
		// Pull the array of badges from user meta, save it.
		$archive_author = ( get_query_var( 'author_name' ) ) ? get_user_by( 'slug', get_query_var( 'author_name') ) : get_userdata( get_query_var( 'author') );
		
		$author_id = $archive_author->ID;		
		$user_badges = get_user_meta( $author_id, 'simplebadges_badges', false );		
		
		// Those not belonging to the displayed user.
		$sbargs = array(
			'post_type' => 'simplebadges_badge',
		);
				
		$sb_query = new WP_Query( $sbargs );
		
		while ( $sb_query->have_posts() ) :$sb_query->the_post();
			
			$badge_id = get_the_ID();
			$badge_image = get_the_post_thumbnail( $badge_id, array( 151, 154 ) );
			$badge_title = get_the_title( $badge_id );
			$badge_dimension = '30';
			$badge_description = get_the_content( $badge_id );
			$badge_permalink = get_permalink( $badge_id );
			
			// Build the list items
			if ( in_array( $badge_id, $user_badges ) ) {	
				
				$badge_link = $this->badge_edit_link( $badge_id, $author_id, 'x' );
				$badge_thumb = $this->badge_thumb( $badge_id, '50' );
				$owned_list .= '<li class="badge-card"><a href="' . $badge_permalink . '">' . $badge_thumb . '</a><h4><a href="' . $badge_permalink . '">' . $badge_title . '</a></h4>' . $badge_link . '<div class="desc">' . $badge_description . '</div></li>';
							
			} else {
				
				$badge_link = $this->badge_edit_link( $badge_id, $author_id, '+' );
				$badge_thumb = $this->badge_thumb( $badge_id, '30' );
				$not_list .= '<li class="badge-card"><a href="' . $badge_permalink . '">' . $badge_thumb . '</a><p><a href="' . $badge_permalink . '">' . $badge_title . '</a></p>' . $badge_link . '</li>';
			
			}
			
			$protect_list = $this->badge_protect( $not_list );
			
		endwhile;
		
		wp_reset_postdata();
		
		$output = '<div class="simplebadges-list"><ul class="owned">' . $owned_list . '</ul><ul class="not">' . $protect_list . '</ul></div>';
						
		// Out it goes, into the world.
		if ( $return ) {
			
			return $output;
		
		} else {
			
			echo $output;
						
		}	
		
	}
	
	
	/*
	 * Update users per badge
	 *
	 */
	
	public function badge_users_update() {
		
		// If true, then we can toggle based on the user and the badge info.
		if ( current_user_can( 'manage_options' ) && isset( $_GET[badgeuser] ) && isset( $_GET[badge] ) ) {
			
			if( check_admin_referer( 'simplebadges_nonce_url' ) ) {
				
				// Set some proper variables so we can get to work
				$badge_toggle_user_id = $_GET[badgeuser];
				$badge_toggle_badge_id = $_GET[badge];
			
				// Debug. Tap tap?
				//echo 'User: ' . $badge_toggle_user_id . ' Badge: ' . $badge_toggle_badge_id;
				
				// Grab and store the array of the user's badges from their meta.
				// See if the badge id in question is in their array or not.
				// If if isn't, add it.
				// If it is, remove  it.

				// Grab this badge's list of user IDs
				// OLD: $badge_toggle_users = get_post_meta( $badge_toggle_badge_id, 'simplebadges_badge_users_frl', false );
				$user_badges = get_user_meta( $badge_toggle_user_id, 'simplebadges_badges', false );
							
				if ( in_array( $badge_toggle_badge_id, $user_badges ) ) {
					
					// Let's toggle and remove the author
					// OLD delete_post_meta( $badge_toggle_badge_id, 'simplebadges_badge_users_frl', $badge_toggle_user_id );
					delete_user_meta( $badge_toggle_user_id, 'simplebadges_badges', $badge_toggle_badge_id );
										
				} else {
					
					// Toggle and add the author
					// OLD add_post_meta( $badge_toggle_badge_id, 'simplebadges_badge_users_frl', $badge_toggle_user_id );	
					add_user_meta( $badge_toggle_user_id, 'simplebadges_badges', $badge_toggle_badge_id );
					
					do_action( 'simplebadges_after_adding', $badge_toggle_user_id, $badge_toggle_badge_id );
					
				}
				
			}
			
		}
			
	}
	
	
	/*
	 * Filter the display of badges
	 *
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
			
			if ( in_array( $badge_id, $user_badges ) ) {
				$badge_winners .= '<a href="' . get_author_posts_url( $id ) . '">' . get_avatar( $id, 30 ) . '</a>';
			}	
			
		}
		
		return $badge_image . $content . $badge_winners;
		
	}
		

// end class
}

// Instnatiate our class
$SimpleBadges = SimpleBadges::getInstance();


// Simple Badges display function
// Just a wrapper for what's in the class.
function simplebadges_user() {
	global $SimpleBadges;
	
	if ( $SimpleBadges ) {
		return $SimpleBadges->author_archive_display();
	}
}


// Simple Badges display function
// Just a wrapper for what's in the class.
function simplebadges_debug() {
	global $SimpleBadges;
	
	if ( $SimpleBadges ) {
//		return $SimpleBadges->badge_users_update();
	}
}



