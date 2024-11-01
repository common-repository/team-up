<?php
	add_action( 'wp_head', function(){ ?>
		<style>
			#rp_4pmqie6tjdvcdb6sx1v4 .rd_staff_p01 .member-info{color:#a1b1bc; background:#ffffff;}#rp_4pmqie6tjdvcdb6sx1v4 .rd_staff_p01 .member-info h3 a{color:#2c3e50;}#rp_4pmqie6tjdvcdb6sx1v4 .rd_staff_p01 .member-info h3 a:hover{color:#af0000 !important;}#rp_4pmqie6tjdvcdb6sx1v4 .rd_staff_p01 .member-social-links a{color:#a1b1bc !important;}#rp_4pmqie6tjdvcdb6sx1v4 .rd_staff_p01 .member-social-links a:hover{color:#af0000 !important;}#rp_4pmqie6tjdvcdb6sx1v4 .rd_staff_p01 .member_desc{color:#a1b1bc; background:#ffffff; border-top:1px solid #ecf0f1;}#rp_4pmqie6tjdvcdb6sx1v4 .staff_post_ctn{ border:1px solid #ecf0f1;}#staff-position.filter_4pmqie6tjdvcdb6sx1v4 #options a {color:#a1b1bc; background:#ffffff; border:1px solid #ecf0f1;}#staff-position.filter_4pmqie6tjdvcdb6sx1v4 #options .selected a{color:#ffffff; background:#af0000; border:1px solid #af0000;}
		</style>
	<?php });

	get_header();

	if($title !== 'no' && strpos( site_url(), 'jet.industries' ) ){ ?>
		<div class="page_title_ctn"> 
			<div class="wrapper table_wrapper">
				<h1>Meet the Team</h1>
				<div id="breadcrumbs">
  					<div id="crumbs"><a href="<?php echo site_url(); ?>">Home</a> <i class="fa-angle-right crumbs_delimiter"></i> <span>Meet the Team</span></div>
  				</div>
			</div>
		</div>

		<div id="staff-position" class="filter_4pmqie6tjdvcdb6sx1v4 filter_center" style="margin-top: 40px; margin-bottom: 0;">
			<ul class="splitter" id="options">
				<li>
					<ul class="staffoptionset" data-option-key="filter">
						<li class="fltr_before"><a href="#filter" data-option-value="*">All</a></li>
						<li class="selected"><a href="#filter" data-option-value="team-up-executive" title="View all post filed under Executive">Executive</a></li>
						<li class="fltr_after"><a href="#filter" data-option-value="team-up-management" title="View all post filed under Management">Management</a></li>
					</ul>
				</li>
			</ul>
		</div>
	<?php } 

	do_action( '__after_page_title' ); ?>

	<div class="section def_section">
  		<div class="wrapper section_wrapper">
			<?php Team_Up::team_up_custom_loop(); ?>
		</div>
	</div>

	<?php

	add_action( 'wp_footer', function(){ ?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('#staff-position li a').on('click', function(){

					$(this).closest('.splitter').find('li').each(function(){ $(this).removeClass('selected'); });
					$(this).closest('li').addClass('selected');

					var value =  $(this).attr('data-option-value');

					if( value != '*' ){
						$('.team-up .team-up-member').not('.'+value).fadeOut(function(){
							$('.team-up .team-up-member.'+value).fadeIn().css("display","inline-block");
						});
					} else {
						$('.team-up-member').fadeIn().css("display","inline-block");
					}
				});
			});
		</script>
	<?php });
	
	get_footer();
?>