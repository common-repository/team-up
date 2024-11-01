<?php
	add_shortcode( 'team-up-group', function( $atts ){
		extract( shortcode_atts(
			array(
				'theme' => 'circle',
				'count' => -1,
				'size'  => 'small',
				'term'  => null,
				'align' => null,
				'offset' => null
			),
			$atts,
			'team-up-group'
		) );


		add_filter( 'body_class', function( $classes ){
			$classes[] = 'team-up-container';
			return $classes;
		});

		$group_args = array(
			'post_type'      => 'team-up',
			'posts_per_page' => $count ? $count : -1,
			'order'          => 'ASC',
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_team_up_member_order',
		);

		if( $term ){
			$group_args['tax_query'] = array(
				array(
					'taxonomy' => 'department',
					'field'    => 'slug',
					'terms'    => $term
				)
			);
		}

		if( $offset ){
			$group_args['offset'] = $offset;
		}

		$group_query = new WP_Query( $group_args );

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
		}';

		wp_add_inline_style( 'team-up-core', $inline_css );

		ob_start();

		if( $group_query->have_posts() ){ ?>
			<div class="team-up team-up-grid team-up-shortcode team-up-shortcode-group team-up-<?php echo $size; ?>" <?php echo ( $align ) ? "style='text-align: $align;'" : ''; ?>>
				<?php while( $group_query->have_posts() ){ $group_query->the_post();
					// Initialize Variables
					$team_id     = get_the_ID();
					$meta        = array();
					$content     = strip_tags( get_the_content() );
					$limit       = 110;

					foreach( get_post_custom( $team_id ) as $key => $value ){
						if( substr( $key, 0, 9 ) == '_team_up_' ){
							$meta[$key] = $value[0];
						}
					}

					if( ! $profile_picture = get_the_post_thumbnail_url( $team_id, 'team_up_tall_crop' ) ){
						$profile_picture = 'https://xhynk.com/placeholder/480/640/'.get_the_title();;
					}

					$classes = 'team-up-member';

					if( $terms = get_the_terms( $team_id, 'department' )){
						$term_list = '';

						foreach( $terms as $term ){
							$term_list .= "{$term->name}, ";
							$classes   .= " team-up-{$term->slug}";
						}
					}

					// Allow Departments to be hidden
					$term_list = ( filter_var( get_option( '_team_up_hide_department' ), FILTER_VALIDATE_BOOLEAN ) ) ? false : $term_list;

					// Allow Square Theme Large Images
					$classes  .= ( filter_var( get_option( '_team_up_square_theme' ), FILTER_VALIDATE_BOOLEAN ) ) ? ' team-up-square' : ''; ?>
					<div class="<?php echo $classes; ?>" data-id="<?php echo ++$counter; ?>">
						<?php /* <div class="team-up-overlay" style="background: url(<?php echo $profile_picture; ?>) center no-repeat;"></div> */ ?>
						<div class="team-up-overlay unsplash" style="background: url(<?php echo plugins_url( '/assets/img/team-up_'. mt_rand( 0, 13 ) .'.jpg', dirname(__FILE__) ); ?>) center no-repeat;"></div>
						<meta name="full-bio" content="<?php echo $content; ?>" />
						<meta name="permalink" content="<?php echo get_permalink(); ?>" />
						<div class="team-up-header">
							<?php printf( '<img class="team-up-profile-picture" src="%s" alt="%s\'s Profile Photo" />', $profile_picture, get_the_title() ); ?>
							<?php if( $size != 'small' ){
								printf( '<h2 class="team-up-name entry-title" itemprop="name"><span>%s</span></h2>', get_the_title() );
								if( $meta['_team_up_job_title'] ) printf( '<h4 class="team-up-job-title">%s</h4>', $meta['_team_up_job_title'] );
								if( $term_list ) echo '<div class="team-up-departments">'. substr( $term_list, 0, -2 ) .'</div>';
							} ?>
						</div>
						<div class="team-up-footer">
							<?php if( $size != 'small' ){ ?>
								<div class="team-up-contact">
									<?php if( !empty( $meta ) ){ ?>
										<ul>
											<?php if( $meta['_team_up_phone'] ){ ?><li><?php echo Team_Up::display_svg( 'phone' ) ?> <?php echo $meta['_team_up_phone'] ?></li><?php } ?>
											<?php if( $meta['_team_up_email'] ){ ?><li><a href="mailto:<?php echo $meta['_team_up_email'] ?>"><?php echo Team_Up::display_svg( 'email' ) ?> <?php echo $meta['_team_up_email'] ?></a></li><?php } ?>
										</ul>
									<?php } ?>
								</div>
							<?php } ?>
							<div class="team-up-bio">
								<?php
									if( $size != 'small' ){
										if( strlen( strip_tags( $content ) ) > $limit ){
											echo substr( $content, 0, strpos( $content, ' ', $limit ) ).'… '. Team_Up::display_svg( 'arrow-right' );
										} else {
											echo $content;
										}
									} else {
										printf( '<h4 class="team-up-name" itemprop="name"><span>%s</span></h4>', get_the_title() );
										printf( '<h6 class="team-up-job-title" itemprop="name"><span>%s</span></h6>', $meta['_team_up_job_title'] );
									}
								?>
							</div>
							<?php if( $size != 'small' ){ ?>
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
							<?php } ?>
						</div>
					</div>
				<?php } wp_reset_postdata(); ?>
			</div>
		<?php } else {
			echo 'No Team Members Found';
		}
		
		return ob_get_clean();
	
	});
?>