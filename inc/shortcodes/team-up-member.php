<?php
	add_shortcode( 'team-up-member', function( $atts ){
		extract( array_merge( array(
			'theme' => 'circle'
		), $atts ) );

		add_filter( 'body_class', function( $classes ){
			$classes[] = 'team-up-container';
			return $classes;
		});


		add_action( 'get_footer', function(){
			if( Team_Up::$popup_counter == 0 ){
				Team_Up::team_up_popup( 'Name', plugins_url( '/assets/img/team-up_1.jpg', dirname(__FILE__) ), 'Biography', 'Title' );
				Team_Up::$popup_counter++;
			}
		}, 1);

		wp_enqueue_style( 'team-up-core' );
		wp_enqueue_script( 'team-up-core' );

		$accent_hex = ( $color = get_option( '_team_up_accent_color' ) ) ? $color : '#006191';
		$accent_rgb = Team_Up::hex_to_rgb( $accent_hex );

		$inline_css = '.team-up-header {
			background-color: rgba('. $accent_rgb .', .45) !important;
		}
		.team-up-header img {
			border-color: '. Team_Up::adjust_brightness( $accent_hex, 100 ) .' !important;
		}
		.team-up-footer {
			background-color: rgba('. $accent_rgb .', .75);
		}
		.team-up-header:after,
		.team-up-header:before {
			border-color: rgba('. Team_Up::hex_to_rgb( Team_Up::adjust_brightness( $accent_hex, 150 ) ) .', .17) !important;
		}
		.team-up-filter a {
			background-color: '. $accent_hex .' !important;
		}
		.team-up-filter a:hover {
			background-color: '. Team_Up::adjust_brightness( $accent_hex, 100 ) .' !important;
		}';

		wp_add_inline_style( 'team-up-core', $inline_css );

		ob_start(); ?>

		<div class="team-up team-up-shortcode team-up-single-member team-up-grid">
			<?php
				// Initialize Variables
				$team_member = get_page_by_title( $name, 'OBJECT', 'team-up');
				$meta        = array();

				$content     = strip_tags( apply_filters( 'the_content', $team_member->post_content ) );
				$limit       = 110;

				foreach( get_post_custom( $team_member->ID ) as $key => $value ){
					if( substr( $key, 0, 9 ) == '_team_up_' ){
						$meta[$key] = $value[0];
					}
				}

				if( ! $profile_picture = get_the_post_thumbnail_url( $team_member->ID, 'team_up_tall_crop' ) ){
					$profile_picture = 'https://xhynk.com/placeholder/480/640/'.get_the_title();;
				}

				$classes = 'team-up-member';

				if( $terms = get_the_terms( $team_member->ID, 'department' )){
					$term_list = '';

					foreach( $terms as $term ){
						$term_list .= "{$term->name}, ";
						$classes   .= " team-up-{$term->slug}";
					}
				}

				// Allow Departments to be hidden
				$term_list = ( filter_var( get_option( '_team_up_hide_department' ), FILTER_VALIDATE_BOOLEAN ) ) ? false : $term_list;

				// Allow Square Theme Large Images
				$classes  .= ( filter_var( get_option( '_team_up_square_theme' ), FILTER_VALIDATE_BOOLEAN ) ) ? ' team-up-square' : '';
			?>
			<div class="<?php echo $classes; ?>" data-id="<?php echo ++$counter; ?>">
				<?php /* <div class="team-up-overlay" style="background: url(<?php echo $profile_picture; ?>) center no-repeat;"></div> */ ?>
				<div class="team-up-overlay unsplash" style="background: url(<?php echo plugins_url( '/assets/img/team-up_'. mt_rand( 0, 13 ) .'.jpg', dirname(__FILE__) ); ?>) center no-repeat;"></div>
					<meta name="full-bio" content="<?php echo $content; ?>" />
					<meta name="permalink" content="<?php echo get_permalink( $team_member->ID ); ?>" />
					<div class="team-up-header">
						<?php printf( '<img class="team-up-profile-picture" src="%s" alt="%s\'s Profile Photo" />', $profile_picture, get_the_title() ); ?>
						<?php printf( '<h2 class="team-up-name entry-title" itemprop="name"><span>%s</span></h2>', $name ); ?>
						<?php if( $meta['_team_up_job_title'] ) printf( '<h4 class="team-up-job-title">%s</h4>', $meta['_team_up_job_title'] ); ?>
						<?php if( $term_list ) echo '<div class="team-up-departments">'. substr( $term_list, 0, -2 ) .'</div>'; ?>
					</div>
					<div class="team-up-footer">
						<div class="team-up-contact">
							<?php if( !empty( $meta ) ){ ?>
								<ul>
									<?php if( $meta['_team_up_phone'] ){ ?><li><?php echo Team_Up::display_svg( 'phone' ) ?> <?php echo $meta['_team_up_phone'] ?></li><?php } ?>
									<?php if( $meta['_team_up_email'] ){ ?><li><a href="mailto:<?php echo $meta['_team_up_email'] ?>"><?php echo Team_Up::display_svg( 'email' ) ?> <?php echo $meta['_team_up_email'] ?></a></li><?php } ?>
								</ul>
							<?php } ?>
						</div>
						<div class="team-up-bio">
							<?php
								if( strlen( strip_tags( $content ) ) > $limit ){
									echo substr( $content, 0, strpos( $content, ' ', $limit ) ).'… '. Team_Up::display_svg( 'arrow-right' );
								} else {
									echo $content;
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
			</div>
		<?php return ob_get_clean();
	});
?>