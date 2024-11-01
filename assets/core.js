jQuery(document).ready(function($){
	// Pop Up Team Member
	$('.team-up-container').on( 'click', '.team-up-header, .team-up-bio svg', function(){
		var $popup      = $('#team-up-popup-container'),
			$teamMember = $(this).closest('.team-up-member');

		var bio   = $teamMember.find('meta[name="full-bio"]').attr('content'),
			href  = $teamMember.find('meta[name="permalink"]').attr('content'),
			name  = $teamMember.find('.team-up-name span').text(),
			title = $teamMember.find('.team-up-header h4').text(),
			photo = $teamMember.find('.team-up-profile-picture').attr('src');

		// Show Popup
		$popup.fadeIn();

		// Set Photo
		if( photo != null && photo != '' && photo.length > 0 )
			$popup.find('.team-up-popup-photo').attr('style', 'background:url('+ photo +') left top no-repeat;');

		// Set Name (and title)
		if( title != null && title != '' && title.length > 0 ){
			$popup.find('.team-up-popup-name').text(name+', '+title);
		} else {
			$popup.find('.team-up-popup-name').text(name);
		}

		// Set Bio
		if( bio != null && bio != '' && bio.length > 0 )
			$popup.find('.team-up-popup-text').scrollTop(0);
			$popup.find('.team-up-popup-text').html(bio);

		// Set Profile Link
		if( href != null && href.length > 0 )
			$popup.find('.team-up-button').attr('href', href);
	});

	// Close the Popup
	function closeTeamUpPopup(){
		var $popup = $('#team-up-popup-container');
		$popup.fadeOut();
	}

	$(document).keyup(function(e) {
		if( e.keyCode == 27 ){
			closeTeamUpPopup();
		}
	});

	$('body').on( 'click', '#team-up-popup-container, .team-up-popup-close, .team-up-popup-close *', function(e){
		if( e.target !== this ) return;

		closeTeamUpPopup();
	});

	$('.team-up-filter').on( 'click', 'a', function(e){
		e.preventDefault();

		var $targetGrid = $(this).closest('.team-up-filter').siblings('.team-up-grid'),
			$buttons    = $(this).siblings(),
			selector    = $(this).attr('data-target'),
			classname   = selector.replace('.', '');
			spacer      = 100; 

		$buttons.each(function(){
			$(this).removeClass('active');
		});

		$(this).addClass('active');

		// I actually think I like the pop-in look a bit better
		//$targetGrid.find('.team-up-member:not('+selector+')').each( function(){
		//	if( $(this).is(':visible') || !$(this).hasClass('team-up-hidden') ){
		//		$(this).addClass('team-up-hidden');
		//	}
		//});
		//setTimeout( function(){
		//	$targetGrid.find(selector).each(function(){
		//		if( $(this).is(':hidden') || $(this).hasClass('team-up-hidden') ){
		//			$(this).removeClass('team-up-hidden');
		//		}
		//	});
		//}, timer );

		$targetGrid.find('.team-up-member').each(function(){
			if( $(this).hasClass(classname)){
				// Show Selected Elements
				if( $(this).hasClass('team-up-hidden') ){
					// Currently Hidden
					$(this).removeClass('team-up-hidden');
				} else {
					// Currently Showing, Fix this later
				}
			} else {
				// Hide the rest
				$(this).addClass('team-up-hidden');
			}
		});

		return false;
	});
});