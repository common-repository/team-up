<?php
/**
	* Plugin Name:	Team Up
	* Description:	Easily Manage Team Members
	* Version:		0.2.2.6
	* Author:		Alex Demchak
	* Author URI:	http://xhynk.com/

	*	Copyright Third River Marketing, LLC, Alex Demchak

	*	This program is free software; you can redistribute it and/or modify
	*	it under the terms of the GNU General Public License as published by
	*	the Free Software Foundation; either version 3 of the License, or
	*	(at your option) any later version.

	*	This program is distributed in the hope that it will be useful,
	*	but WITHOUT ANY WARRANTY; without even the implied warranty of
	*	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	*	GNU General Public License for more details.

	*	You should have received a copy of the GNU General Public License
	*	along with this program.  If not, see http://www.gnu.org/licenses.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class Team_Up {
	/**
	 * Set the Class Instance
	 */
	static $instance;
	static $popup_counter = 0;
	static $shortcode_counter = 0;
	static $social_networks   = array(
		'Facebook',
		'Instagram',
		'LinkedIn',
		'Twitter'
	);

	public static function get_instance(){
		if( ! self::$instance )
			self::$instance = new self();

		return self::$instance;
	}

	public function issetor( &$var, $default = false ){
		return isset( $var ) ? $var : $default;
	}

	public static function display_svg( $icon = '', $class = '', $atts = '', $echo = false ){
		require dirname(__FILE__).'/inc/svg-icons.php';

		if( $echo === true ){
			echo $svg;
		} else {
			return $svg;
		}
	}

	public static function adjust_brightness( $hex, $steps ){
		// Steps should be between -255 and 255. Negative = darker, positive = lighter
		$steps = max( -255, min( 255, $steps ) );

		// Normalize into a six character long hex string
		$hex = str_replace( '#', '', $hex );
		if( strlen( $hex ) == 3 ){
			$hex = str_repeat( $hex[0], 2 ) . str_repeat( $hex[1], 2 ) . str_repeat( $hex[2], 2 );
		}

		foreach( str_split( $hex, 2 ) as $color ){
			$color   = hexdec( $color ); // Convert to decimal
			$color   = max( 0, min( 255, $color + $steps ) ); // Adjust color
			$return .= str_pad( dechex( $color ), 2, '0', STR_PAD_LEFT ); // Make two char hex code
		}

		return "#$return";
	}

	public static function hex_to_rgb( $hex ){
		list( $r, $g, $b ) = sscanf( $hex, "#%02x%02x%02x" );
		return "$r, $g, $b";
	}

	public function __construct(){
		add_action( 'init', [$this, 'create_post_type'], 12 );
		add_action( 'save_post', [$this, 'save_meta'], 10, 1 );
		add_action( 'admin_init', [$this, 'show_all_team_members'] );
		add_action( 'admin_menu', [$this, 'register_admin_page'] );
		//add_action( 'pre_get_posts', [$this, 'reorder_team_members'], 10, 1 );
		//add_action( 'pre_get_posts', [$this, 'reorder_team_members'], 1, 1 );
		add_action( 'pre_get_posts', [$this, 'reorder_team_members'], 99, 1 );
		add_action( 'add_meta_boxes', [$this, 'add_meta_box'], 1, 2 );
		add_action( 'after_setup_theme', [$this, 'add_image_sizes'] );
		add_action( 'after_setup_theme', [$this, 'register_shortcodes'] );
		add_action( 'after_setup_theme', [$this, 'register_scripts'] );
		add_action( 'save_post_team-up', [$this, 'default_team_member_order'], 10, 2 );
		add_action( 'wp_enqueue_scripts', [$this, 'enqueue_scripts' ] );
		add_action( 'admin_enqueue_scripts', [$this, 'exclusive_admin_assets'] );
		add_action( 'manage_team-up_posts_custom_column' , [$this, 'manage_column_content'], 10, 2 );

		add_action( 'team_up_archive_filter', [$this, 'team_up_archive_default_filter'] );

		add_action( 'wp_ajax_modify_team_up_option', [$this, 'modify_team_up_option'] );
		add_action( 'wp_ajax_toggle_team_up_option', [$this, 'toggle_team_up_option'] );
		add_action( 'wp_ajax_reset_team_member_order', [$this, 'reset_team_member_order'] );
		add_action( 'wp_ajax_toggle_team_up_post_meta', [$this, 'toggle_team_up_post_meta'] );
		add_action( 'wp_ajax_update_team_member_order', [$this, 'update_team_member_order'] );

		add_filter( 'views_edit-team-up', [$this, 'add_order_reset_button'] );
		add_filter( 'single_template', [$this, 'single_template'], 10 );
		add_filter( 'archive_template', [$this, 'archive_template'], 10 );
		add_filter( 'post_updated_messages', [$this, 'team_up_updated_messages'] );
		add_filter( 'manage_team-up_posts_columns', [$this, 'manage_columns'] );
	}

	public function default_team_member_order( $ID, $post ){
		// If No Member Order is Set
		if( ! $team_member_order = get_post_meta( $ID, '_team_up_member_order', true ) ){
			$update_query = new WP_Query( array(
				'post_type'      => 'team-up',
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_team_up_member_order',
				'posts_per_page' => -1,
				'meta_query'     => array(
					'key'     => '_team_up_member_order',
					'value'   => $meta_values,
					'type'    => 'numeric',
					'compare' => 'BETWEEN',
				)
			) );

			if( ! $update_query->have_posts() || $update_query->post_count == 0 ){
				// Set this to the first
				update_post_meta( $ID, '_team_up_member_order', 1 );
			} else {
				$order = $update_query->post_count + 1;
				update_post_meta( $ID, '_team_up_member_order', $order );
			}
		}
	}

	public function team_up_archive_default_filter(){
		// Get Terms for Filtering
		$terms = get_terms( array( 'taxonomy' => 'department', 'hide_empty' => true ) );
		if( count( $terms ) > 1 ){
			// If at least 2 terms
			echo '<section class="team-up-filter">';
				// Default All
				echo "<a class='button' href='#all' data-target='.team-up-member'>All</a>";
				foreach( $terms as $term ){
					echo "<a class='button' href='#{$term->slug}' data-target='.team-up-{$term->slug}'>{$term->name}</a>";
				}
			echo '</section>';
		}
	}

	public static function team_up_custom_loop(){
		$counter = 0;
		global $wp_query;
		$wp_query->set( 'posts_per_page', -1 );

		if( have_posts() ) :
			do_action( 'team_up_archive_filter' );

			echo '<div class="team-up team-up-grid">';
				while( have_posts() ) : the_post(); ?>
				<?php
					// Initialize Variables
					$post_id = get_the_ID();
					$meta = array();

					$content  = get_the_content();
					$limit    = 110;

					foreach( get_post_custom( $post_id ) as $key => $value ){
						if( substr( $key, 0, 9 ) == '_team_up_' ){
							$meta[$key] = $value[0];
						}
					}

					if( ! $profile_picture = get_the_post_thumbnail_url( $post_id, 'team_up_tall_crop' ) ){
						$profile_picture = 'https://xhynk.com/placeholder/480/640/'.get_the_title();
					}

					$classes = 'team-up-member';

					if( $terms = get_the_terms( $post_id, 'department' )){
						$term_list = '';

						foreach( $terms as $term ){
							$term_list .= "{$term->name}, ";
							$classes   .= " team-up-{$term->slug}";
						}
					} else {
						$term_list = '';
						$classes = 'team-up-member';
					}

					// Allow Departments to be hidden
					$term_list = ( filter_var( get_option( '_team_up_hide_department' ), FILTER_VALIDATE_BOOLEAN ) ) ? false : $term_list;

					// Allow Square Theme Large Images
					$classes  .= ( filter_var( get_option( '_team_up_square_theme' ), FILTER_VALIDATE_BOOLEAN ) ) ? ' team-up-square' : '';
				?>
				<div class="<?php echo $classes; ?>" data-id="<?php echo ++$counter; ?>">
					<?php /* <div class="team-up-overlay" style="background: url(<?php echo $profile_picture; ?>) center no-repeat;"></div> */ ?>
					<div class="team-up-overlay unsplash" style="background: url(<?php echo plugins_url( '/assets/img/team-up_'. mt_rand( 0, 13 ) .'.jpg', dirname(__FILE__) ); ?>) center no-repeat;"></div>
						<meta name="full-bio" content="<?php echo esc_attr($content); ?>" />
						<meta name="permalink" content="<?php echo get_permalink(); ?>" />
						<div class="team-up-header">
							<?php printf( '<img class="team-up-profile-picture" src="%s" alt="%s\'s Profile Photo" />', $profile_picture, get_the_title() ); ?>
							<?php printf( '<h2 class="team-up-name entry-title" itemprop="name"><span>%s</span></h2>', get_the_title() ); ?>
							<?php if( $meta['_team_up_job_title'] ) printf( '<h4 class="team-up-job-title">%s</h4>', $meta['_team_up_job_title'] ); ?>
							<?php if( $term_list ) echo '<div class="team-up-departments">'. substr( $term_list, 0, -2 ) .'</div>'; ?>
						</div>
						<div class="team-up-footer">
							<?php if( $meta['_team_up_phone'] || $meta['_team_up_email'] ){ ?>
								<div class="team-up-contact">
									<ul>
										<?php if( $meta['_team_up_phone'] ){ ?><li><?php echo Team_Up::display_svg( 'phone' ) ?> <?php echo $meta['_team_up_phone'] ?></li><?php } ?>
										<?php if( $meta['_team_up_email'] ){ ?><li><a href="mailto:<?php echo $meta['_team_up_email']; ?>"><?php echo Team_Up::display_svg( 'mail' ); ?> <?php echo $meta['_team_up_email']; ?></a></li><?php } ?>
									</ul>
								</div>
							<?php } else { echo '<div style="height: 6px;"></div>'; } ?>
						<div class="team-up-bio">
							<?php
								$bio_content = strip_tags( $content );

								if( strlen( $bio_content ) > $limit ){
									echo substr( $bio_content, 0, strpos( $bio_content, ' ', $limit ) ).'… '. Team_Up::display_svg( 'arrow-right' );
								} else {
									echo $bio_content;
								}
							?>
						</div>
						<div class="team-up-social">
							<?php
								$social_networks = maybe_unserialize( $meta['_team_up_social'] );

								foreach( Team_Up::$social_networks as $network ){
									$_network = strtolower($network);

									if( $account = $social_networks[$_network] ){
										echo '<a href="'. $account .'" target="_blank" title="'. $network .'">'. Team_Up::display_svg( $_network, 'team-up-social-'.$_network ) .'</a>';
									}
								}
							?>
						</div>
					</div>
				</div>
				<?php if( is_post_type_archive( 'team-up' ) && filter_var( get_post_meta( $post_id, '_team_up_break_after', true ), FILTER_VALIDATE_BOOLEAN ) ) echo '<div style="clear: both;"></div>'; ?>
				<?php endwhile;
			echo '</div>';
		endif;
	}

	public function register_shortcodes(){
		require_once plugin_dir_path( __FILE__ ) . 'inc/shortcodes-controller.php';
	}

	public function json_response( $status = 501, $message = '', $additional_info = null ){
		$response = [];

		$response['status']  = $status;
		$response['message'] = $message;

		if( $additional_info ){
			foreach( $additional_info as $key => $value ){
				$response[$key] = $value;
			}
		}

		echo json_encode( $response );
		wp_die();
	}

	public function show_all_team_members(){
		$user_id = get_current_user_id();
		update_user_meta( $user_id, 'edit_team-up_per_page', 999 );
	}

	public function reorder_team_members( $query ){
		// This is allowed (required?) on Admin and Archive
		if( $query->is_main_query() ) {
			if( is_post_type_archive( 'team-up' ) || is_tax( 'department' ) ){
				$query->set( 'posts_per_page', -1 );
				$query->set( 'meta_key', '_team_up_member_order' );
				$query->set( 'orderby', array( 'meta_value_num' => 'ASC' ) );

				if( isset( $_GET['dept'] ) ){
					$terms = array();
					$departments = explode( ',', urldecode( $_GET['dept'] ) );

					$tax_query = array(
						array(
							'taxonomy' => 'department',
							'field'    => 'slug',
							'terms'    => $departments,
							'operator' => 'IN'
						)
					);

					$query->set( 'tax_query', $tax_query );
				}
			}
		}
	}

	public function update_team_member_order(){
		if( ! $_POST ){
			wp_die( 'Please do not call this function directly.' );
		} else {
			extract( $_POST );
		}

		if( ! $postID || ! $oldPosition || ! $newPosition && ( $newPosition != $oldPosition ) )
			$this->json_response( 403, 'No Values Detected, Exiting.' );

		if( $newPosition < $oldPosition ){
			// Moved Up Towards Front
			$direction = 'up';
			$meta_values = array( $newPosition, $oldPosition - 1 );
		} else if( $newPosition > $oldPosition ){
			// Moved Down Towards Bottom
			$direction = 'down';
			$meta_values = array( $oldPosition + 1, $newPosition );
		} else {
			// Something Weird Happened, or ended in same position?
			$direction = null;
			$this->json_response( 403, 'Nothing to do, exiting.' );
		}

		$update_query = new WP_Query( array(
			'post_type'      => 'team-up',
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_team_up_member_order',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'key'     => '_team_up_member_order',
				'value'   => $meta_values,
				'type'    => 'numeric',
				'compare' => 'BETWEEN',
			)
		) );

		if( $update_query->have_posts() ){
			while( $update_query->have_posts() ){
				$update_query->the_post();
				$post_id = get_the_ID();

				$currentPosition = get_post_meta( $post_id, '_team_up_member_order', true );

				if( $direction == 'up' ){
					$shiftedPosition = $currentPosition + 1;
				} else if( $direction == 'down' ) {
					$shiftedPosition = $currentPosition - 1;
				}

				if( update_post_meta( $post_id, '_team_up_member_order', $shiftedPosition ) ){
					// Good, Posts are updating.
				} else {
					$this->json_response( 403, 'Something went wrong, '. get_the_title() .' could not be updated.' );
				}
			}
			wp_reset_postdata();

			// If we didn't exit with json_response yet, all posts are updated. Now update the moved one
			if( update_post_meta( $postID, '_team_up_member_order', $newPosition, $oldPosition ) ){
				$this->json_response( 200, 'All done!' );
			}
		} else {
			$this->json_response( 200, 'Nothing to do.' );
		}
	}

	public function reset_team_member_order(){
		if( ! $_POST ){
			wp_die( 'Please do not call this function directly.' );
		} else {
			extract( $_POST );
		}

		if( ! $postID || ! $newPosition )
			$this->json_response( 403, 'No Values Detected, Exiting.' );

		if( update_post_meta( $postID, '_team_up_member_order', $newPosition ) ){
			// Good, Posts are updating.
		} else {
			if( $newPosition == get_post_meta( $postID, '_team_up_member_order', true ) ){
				$this->json_response( 200, $title.' is already at position '.$newPosition );
			} else {
				$this->json_response( 403, 'Something went wrong, '. $title .' could not be updated. [postID:'.$postID.',position:'.$newPosition.']' );
			}
		}
	}

	public function add_meta_box( $post_type, $post ){
		add_meta_box( 'team-up-member-metabox', "Team Member Additional Information", [$this, 'team_up_metabox'], 'team-up', 'normal', 'high' );
	}

	public function team_up_metabox(){ ?>
		<?php
			$meta = get_post_custom( $post->ID );

			foreach( $meta as $key => $value ){
				if( substr( $key, 0, 9 ) == '_team_up_' ){
					${$key} = $value[0];
				}
			}

			$_team_up_social = maybe_unserialize( $_team_up_social );

			wp_nonce_field( 'save_post', 'team_up_meta_nonce' );
		?>
		<div class="input-container">
			<label>
				<strong>Job Title: </strong>
				<input type="text" name="_team_up_job_title" id="team_up_job_title" placeholder="Job Title" value="<?php echo esc_attr( $_team_up_job_title ); ?>" />
			</label>
		</div><br />
		<div class="input-container">
			<label>
				<strong>Phone Number or Extension: </strong>
				<input type="text" name="_team_up_phone" id="_team_up_phone" placeholder="(123) 456-7890/ext. 123" value="<?php echo esc_attr( $_team_up_phone ); ?>" />
			</label>
		</div><br />
		<div class="input-container">
			<label>
				<strong>Alternate Phone: </strong>
				<input type="text" name="_team_up_alt_phone" id="_team_up_alt_phone" placeholder="(123) 456-7890/ext. 123" value="<?php echo esc_attr( $_team_up_alt_phone ); ?>" />
			</label>
		</div><br />
		<div class="input-container">
			<label>
				<strong>Email Address: </strong>
				<input type="text" name="_team_up_email" id="_team_up_email" placeholder="name@example.com" value="<?php echo esc_attr( $_team_up_email ); ?>" />
			</label>
		</div><br />
		<strong>Social Media Links: </strong>
		<div class="input-container">
			<br />
			<?php
				foreach( self::$social_networks as $network ){ ?> 
					<label>
						<strong><?php echo $network; ?>: </strong>
						<input type="text" name="_team_up_social[<?php echo strtolower( $network ); ?>]" id="_team_up_social[<?php echo strtolower( $network ); ?>]" value="<?php echo esc_attr( $_team_up_social[strtolower($network)] ); ?>" />
					</label>
				<?php }
			?>
		</div><br />
		<div class="input-container">
			<label>
				<strong>More Information (Shown on Individual Team Member Page): </strong><br /><br />
				<?php wp_editor(  $_team_up_more_details, '_team_up_more_details', array( 'drag_drop_upload' => true, 'wpautop' => true ) );  ?>
			</label>
		</div><br />
	<?php }

	public function save_meta( $post_id ){
		if( isset( $_POST['team_up_meta_nonce'] ) && wp_verify_nonce( $_POST['team_up_meta_nonce'], 'save_post' ) ){
			$keys = array(
				'_team_up_job_title',
				'_team_up_phone',
				'_team_up_alt_phone',
				'_team_up_email',
				'_team_up_more_details',
				'_team_up_social',
			);

			foreach( $keys as $key ){
				if( isset( $_POST[$key] ) && $_POST[$key] != '' ){
					update_post_meta( $post_id, $key, $_POST[$key] );
				} else {
					delete_post_meta( $post_id, $key );
				}
			}
		}
	}

	public function create_post_type(){
		register_post_type( 'team-up',
			array(
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => [
					'slug'           	=> 'team',
					'with_front'     	=> true
				],
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_icon'          => 'dashicons-id-alt',
				'menu_position'      => null,
				'supports'           => ['title', 'editor', 'revisions', 'thumbnail', 'custom-fields', /*'genesis-cpt-archives-settings', 'genesis'*/ ],
				'labels'             => [
					'name'           	    => __( 'Team Members' ),
					'singular_name'         => __( 'Team Member' ),
					'add_new'               => __( 'Add New' ),
					'add_new_item'          => __( 'Add New Team Member' ),
					'edit_item'             => __( 'Edit Team Member' ),
					'new_item'              => __( 'New Team Member' ),
					'view_item'             => __( 'View Team Member' ),
					'view_items'            => __( 'View Team Members' ),
					'search_items'          => __( 'Search Team Members' ),
					'all_items'             => __( 'All Team Members' ),
					'insert_into_item'      => __( 'Insert into Team Member' ),
					'featured_image'        => __( 'Profile Picture' ),
					'set_featured_image'    => __( 'Set Profile Picture' ),
					'remove_featured_image' => __( 'Remove Profile Picture' ),
					'use_featured_image'    => __( 'Use Profile Picture' )
				],
			)
		);

		register_taxonomy( 'department', 'team-up',
			array(
				'labels' => [
					'name'              => __( 'Departments' ),
					'singular_name'     => __( 'Department' ),
					'search_items'      => __( 'Search Departments' ),
					'all_items'         => __( 'All Departments' ),
					'parent_item'       => __( 'Parent Department' ),
					'parent_item_colon' => __( 'Parent Department:' ),
					'edit_item'         => __( 'Edit Department' ),
					'update_item'       => __( 'Update Department' ),
					'add_new_item'      => __( 'Add New Department' ),
					'new_item_name'     => __( 'New Department Name' ),
					'menu_name'         => __( 'Departments' )
				],
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => [
					'slug' => 'department'
				],
			)
		);

		flush_rewrite_rules();
	}

	public function add_image_sizes(){
		$image_sizes = array(
			['team_up_wide', 900, 600, false],
			['team_up_wide_crop', 900, 600, true],
			['team_up_tall', 480, 640, false],
			['team_up_tall_crop', 480, 640, true]
		);

		foreach( $image_sizes as $image_size ){
			add_image_size( $image_size[0], $image_size[1], $image_size[2], $image_size[3] );
		}
	}

	public function register_scripts(){
		$assets_dir = plugins_url( '/assets', __FILE__ );
		
		wp_register_script( 'team-up-core', $assets_dir.'/core.js', ['jquery'], filemtime( plugin_dir_path( __FILE__ ) . 'assets/core.js' ), true );
		wp_register_style( 'team-up-core', $assets_dir.'/core.css', [], filemtime( plugin_dir_path( __FILE__ ) . 'assets/core.css' ) );
	}

	public function enqueue_scripts(){
		global $post;

		if( is_post_type_archive( 'team-up' ) || is_singular( 'team-up' ) || is_tax( 'department' ) ){
			$assets_dir = plugins_url( '/assets', __FILE__ );

			wp_enqueue_script( 'team-up-core' );
			wp_enqueue_style( 'team-up-core' );
		}
	}

	public function exclusive_admin_assets( $hook ){
		$hook_array = array( 'edit.php', 'toplevel_page_team-up-options', 'team-up_page_team-up-options');

		if( in_array( $hook, $hook_array ) && ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'team-up' || isset( $_GET['page'] ) && $_GET['page'] == 'team-up-options' ) ){
			$assets_dir = plugins_url( '/assets', __FILE__ );

			wp_enqueue_style( 'wp-color-picker' );
    		wp_enqueue_script( 'wp-color-picker');

			wp_enqueue_script( 'tableDnD-team-up', "$assets_dir/tableDnD.min.js", array('jquery'), '1.0.3', true );
			wp_enqueue_script( 'team-up-admin', "$assets_dir/admin.js", array('jquery', 'tableDnD-team-up'), filemtime( plugin_dir_path( __FILE__ ) . 'assets/admin.js' ), true );
			wp_enqueue_style( 'team-up-admin', "$assets_dir/admin.css", null, filemtime( plugin_dir_path( __FILE__ ) . 'assets/admin.css' ) );
		}
	}

	public function manage_columns( $columns ){
		// Move Draggable Column to Front
		$new_columns = array();
		foreach( $columns as $id => $title ){
			if( $id == 'cb' ) // Insert Order Column First
				$new_columns['team-up-draggable'] = 'Order';

			$new_columns[$id] = $title;
		}

		// Add "Break After" Column
		$new_columns['team-up-break-after'] = 'Break';

		return $new_columns;
	}

	public function manage_column_content( $column, $post_id ){
		switch( $column ){
			case 'team-up-draggable':
				echo '<div data-id="'. $post_id .'" data-position="'. get_post_meta( $post_id, '_team_up_member_order', true ) .'"><span style="display: none;">'. get_post_meta( $post_id, '_team_up_member_order', true ) .'</span>'. Team_Up::display_svg( 'menu', 'drag', 'style="width: 16px;"' ) .'</div>';
				break;

			case 'team-up-break-after':
				$enabled = !empty( get_post_meta( $post_id, '_team_up_break_after', true ) ) ? 'enabled' : 'disabled';

				echo "<div class='team-up-break team-up-checkbox $enabled' data-state='$enabled' data-option='_team_up_break_after'><div class='team-up-check'>";
					echo Team_Up::display_svg( 'checkmark', 'icon' );
				echo '</div></div>';
			
				break;
		}
	}

	function add_order_reset_button( $views ){
		$views['reset-member-order'] = '<button id="reset-member-order" type="button" class="wp-core-ui button secondary" style="margin-top: -5px; margin-bottom: 10px;">Reset Member Order '. Team_Up::display_svg( 'rotate-ccw', 'redo', 'style="width: 14px; position: relative; top: 2px; right: -4px;"' ) .'</button>';
		return $views;
	}

	public function register_admin_page(){
		add_submenu_page( 'edit.php?post_type=team-up', 'Team Up Options', 'Team Up Options', 'manage_options', 'team-up-options', function(){ require_once dirname(__FILE__).'/inc/admin-panel.php'; } );
	}

	public function toggle_team_up_option(){
		if( ! $_POST ){
			wp_die( 'Please do not call this function directly' );
		} else {
			extract( $_POST );
		}

		if( ! $currentState || ! $optionName )
			$this->json_response( 403, 'No Values Detected' );

		// Prevent Modifying other options
		if( substr( $optionName, 0, 9 ) != '_team_up_' )
			$this->json_response( 403, 'Illegal Option' );

		if( $currentState == 'enabled' ){
			$newState = false;
			$displayNewState = 'disabled';
		} else if( $currentState == 'disabled' ){
			$newState = true;
			$displayNewState = 'enabled';
		} else {
			$this->json_response( 403, 'Unauthorized Values Detected.', ['newState' => ( filter_var( get_option( $optionName ), FILTER_VALIDATE_BOOLEAN ) ) ? 'enabled' : 'disabled'] );
		}

		// Make sure newState is a boolean (true/false) value, otherwise discard and reset.
		if( is_bool( $newState ) && update_option( $optionName, $newState ) ){
			$this->json_response( 200, $optionDisplayName. ' has been <strong>'. ucwords( $displayNewState ) .'</strong>.', ['newState' => $displayNewState]);
		} else {
			$this->json_response( 400, 'Request Failed.', ['newState', $currentState] );
		}
	}

	public function toggle_team_up_post_meta(){
		if( ! $_POST ){
			wp_die( 'Please do not call this function directly' );
		} else {
			extract( $_POST );
		}

		if( ! $currentState || ! $optionName || ! $postID )
			$this->json_response( 403, 'No Values Detected' );

		// Prevent Modifying other options
		if( substr( $optionName, 0, 9 ) != '_team_up_' )
			$this->json_response( 403, 'Illegal Meta Field' );

		if( $currentState == 'enabled' ){
			$newState = false;
			$displayNewState = 'disabled';
		} else if( $currentState == 'disabled' ){
			$newState = true;
			$displayNewState = 'enabled';
		} else {
			$this->json_response( 403, 'Unauthorized Values Detected.', ['newState' => ( filter_var( get_option( $optionName ), FILTER_VALIDATE_BOOLEAN ) ) ? 'enabled' : 'disabled'] );
		}

		// Make sure newState is a boolean (true/false) value, otherwise discard and reset.
		if( is_bool( $newState ) && update_post_meta( $postID, $optionName, $newState ) ){
			$this->json_response( 200, 'Break after <strong>'. $postTitle .'</strong> has been <strong>'. ucwords( $displayNewState ) .'</strong>.', ['newState' => $displayNewState]);
		} else {
			$this->json_response( 400, 'Request Failed.', ['newState', $currentState] );
		}
	}

	public function modify_team_up_option(){
		if( ! $_POST ){
			wp_die( 'Please do not call this function directly' );
		} else {
			extract( $_POST );
		}

		if( ! $newValue || ! $optionName )
			$this->json_response( 403, 'No Values Detected' );

		// Prevent Modifying other options
		if( substr( $optionName, 0, 9 ) != '_team_up_' )
			$this->json_response( 403, 'Illegal Option' );

		// Prevent Tags in standard fields
		$newValue = sanitize_text_field( $newValue );

		if( update_option( $optionName, $newValue ) ){
			$this->json_response( 200, $optionDisplayName. ' has been set to <strong>'. esc_html( ucwords( $newValue ) ) .'</strong>.' );
		} else {
			$this->json_response( 400, 'Request Failed.' );
		}
	}

	public function single_template( $single_template ){
		global $post;
		
		if( is_singular( 'team-up' ) && function_exists( 'genesis' ) )
			$single_template = dirname(__FILE__).'/inc/single-team-up.php';

		return $single_template;
	}

	public function archive_template( $archive_template ){
		if( is_post_type_archive( 'team-up' ) || ( is_tax( 'department' ) && get_post_type() == 'team-up' ) )
			$archive_template = dirname(__FILE__).'/inc/archive-team-up.php';

		return $archive_template;
	}

	public function team_up_updated_messages( $messages ){
		$post             = get_post();
		$post_type        = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );

		$messages['team-up'] = array(
			0  => '',
			1  => __( 'Team Member Updated.', 'team-up' ),
			2  => __( 'Custom Field updated.', 'team-up' ),
			3  => __( 'Custom Field deleted.', 'team-up' ),
			4  => __( 'Team Member updated.', 'team-up' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Team Member restored to revision from %s', 'team-up' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Team Member Created.', 'team-up' ),
			7  => __( 'Team Member Saved.', 'team-up' ),
			8  => __( 'Team Member Submitted.', 'team-up' ),
			9  => sprintf(
				__( 'Team Member Scheduled for: <strong>%1$s</strong>.', 'team-up' ),
				date_i18n( __( 'M j, Y @ G:i', 'team-up' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Team Member Draft Updated.', 'team-up' )
		);

		if( $post_type_object->publicly_queryable && 'team-up' === $post_type ){
			$permalink = get_permalink( $post->ID );

			$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View Team Member', 'team-up' ) );
			$messages[$post_type][1] .= $view_link;
			$messages[$post_type][6] .= $view_link;
			$messages[$post_type][9] .= $view_link;

			$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
			$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview Team Member', 'team-up' ) );

			$messages[$post_type][8]  .= $preview_link;
			$messages[$post_type][10] .= $preview_link;
		}

		return $messages;
	}

	public static function team_up_popup( $name, $photo, $bio, $title ){ ?>
		<?php // Define Variables
			$display_name = ( $title ) ? "$name, $title" : $name;
			$accent_hex = ( $color = get_option( '_team_up_accent_color' ) ) ? $color : '#006191';
		?>
		<div id="team-up-popup-container" style="display: none;">
			<div id="team-up-popup" class="team-up-popup" style="background-color: <?php echo $accent_hex; ?>;">
				<div class="team-up-popup-bio">
					<div class="team-up-popup-photo" style="background:url(<?php echo $photo; ?>) left top no-repeat;"></div>
					<div class="team-up-popup-content">
						<div class="clearfix"></div>
						<div class="team-up-popup-text"><?php echo $bio; ?></div>
					</div>
					<div class="team-up-popup-name"><?php echo $display_name; ?></div>
				</div>
				<div class="team-up-popup-close"><?php echo Team_Up::display_svg( 'x-circle' ); ?></div>
				<?php if( function_exists('genesis') ){ ?>
					<div class="team-up-button-container">
						<a href="#" style="background-color: <?php echo $accent_hex; ?>; border: 1px solid <?php echo Team_Up::adjust_brightness( $accent_hex, -25 ); ?>; color: #fff;" class="team-up-button">View Profile</a>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php }
}

add_action( 'plugins_loaded', ['Team_Up', 'get_instance'] );