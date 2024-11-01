<?php
	add_filter( 'post_class', function( $classes ){
		$classes[] = 'team-up single-team-member';

		return $classes;
	});

	remove_action( 'genesis_after_header', 'agentfocused_large_featured_image' );

	remove_action( 'genesis_loop', 'genesis_do_loop' );
	add_action( 'genesis_loop', function(){
		$post_id = get_the_ID();
		$name    = get_the_title();
		$meta    = array();

		foreach( get_post_custom( $post_id ) as $key => $value ){
			if( substr( $key, 0, 9 ) == '_team_up_' ){
				$meta[$key] = $value[0];
			}
		}

		the_post();
		?>
		<article <?php post_class(); ?>>
			<header class="entry-header">
				<h1 class="entry-title" itemprop="headline"><?php echo $name; ?></h1>
			</header>
			<div class="entry-content" itemprop="text">
				<?php
					if( $profile_picture = get_the_post_thumbnail_url( $post_id, 'team_up_tall_crop' ) ){
						printf( '<img class="team-up-profile-picture alignleft" src="%s" alt="%s" />', $profile_picture, $name );
					}

					printf( '<section class="team-up-content"><h4 class="widget-title">About '. $name .'</h4><div class="team-up-widget team-up-full-bio">%s</div></section>', get_the_content() );
				?>
				<?php if( $meta['_team_up_phone'] || $meta['_team_up_alt_phone'] || $meta['_team_up_email'] ){ ?>
					<section class="team-up-content">
						<h4 class="widget-title">Contact Information</h4>
						<div class="team-up-widget team-up-contact">
							<?php if( $meta['_team_up_phone'] ){ ?><h4><?php echo Team_Up::display_svg( 'phone' ) ?> <?php echo $meta['_team_up_phone'] ?></h4><?php } ?>
							<?php if( $meta['_team_up_alt_phone'] ){ ?><h4><?php echo Team_Up::display_svg( 'smartphone' ) ?> <?php echo $meta['_team_up_alt_phone'] ?></h4><?php } ?>
							<?php if( $meta['_team_up_email'] ){ ?><h4><a href="mailto:<?php echo $meta['_team_up_email'] ?>"><?php echo Team_Up::display_svg( 'mail' ) ?> <?php echo $meta['_team_up_email'] ?></a></h4><?php } ?>
						</div>
					</section>
				<?php } ?>
				<?php if( array_shift( $social_networks = maybe_unserialize( $meta['_team_up_social'] ) != 0 ) ){ ?>
					<section class="team-up-content">
						<h4 class="widget-title">Social Media</h4>
						<div class="team-up-widget team-up-social">
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
					</section>
				<?php } ?>
				<?php if( $meta['_team_up_more_details'] ) echo apply_filters( 'the_content', '<section class="team-up-content"><div class="team-up-widget team-up-more-details">'. $meta['_team_up_more_details'] .'</div></section>' ); ?>
			</div>
		</article>
	<?php });

	genesis();
?>