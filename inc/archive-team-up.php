<?php
#error_reporting( E_ALL );
#ini_set('display_errors', 1);
	/**
	 * Team Up Team Members Archive
	 */

	// Add Body Class
	add_filter( 'body_class', function( $classes ){
		$classes[] = 'team-up-container';

		return $classes;
	});

	// Maybe Force Layout
	if( get_option( '_team_up_force_genesis_full_width' ) == true ){
		add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
	}

	add_action( 'wp_head', function(){
		$accent_hex = ( $color = get_option( '_team_up_accent_color' ) ) ? $color : '#006191';
		$accent_rgb = Team_Up::hex_to_rgb( $accent_hex ); ?>
		<style>
			.team-up-header {
				background-color: rgba(<?php echo $accent_rgb; ?>, .45) !important;
			}
			.team-up-header img {
				border-color: <?php echo Team_Up::adjust_brightness( $accent_hex, 100 ); ?> !important;
			}
			.team-up-footer {
				background-color: rgba(<?php echo $accent_rgb; ?>, .75);
			}
			.team-up-header:after,
			.team-up-header:before {
				border-color: rgba(<?php echo Team_Up::hex_to_rgb( Team_Up::adjust_brightness( $accent_hex, 150 ) ); ?>, .17) !important;
			}
			.team-up-filter a.button {
				background-color: <?php echo $accent_hex; ?> !important;
			}
			.team-up-filter a.button:hover,
			.team-up-filter a.button.active {
				background-color: <?php echo Team_Up::adjust_brightness( $accent_hex, 20 ); ?> !important;
			}
		</style>
	<?php });

	// Add Popup
	add_action( 'wp_footer', function(){
		Team_Up::team_up_popup( 'Name', plugins_url( '/assets/img/team-up_1.jpg', dirname(__FILE__) ), 'Biography', 'Title' );
	});

	if( function_exists( 'genesis' ) ){
		// Remove Default Loop
		remove_action( 'genesis_loop', 'genesis_do_loop' );

		// Hook in a new loop
		add_action( 'genesis_before_loop', function($query){
			$term = get_term_by( 'slug', get_query_var('term'), get_query_var('taxonomy') );
			
			$_term = ( $term ) ? "<span class='post-type-term'>: {$term->name}</span>" : '';

			echo '<header class="entry-header">
				<h1 class="entry-title" itemprop="headline"><span class="post-type-name">Team Members</span>'. $_term .'</h1>
			</header>';
		});

		add_action( 'genesis_loop', ['Team_Up', 'team_up_custom_loop'] );

		genesis();
	} else {
		include_once( dirname(__FILE__).'/custom-archive-template.php' );
	}
?>