jQuery(document).ready(function($){
	// Alert Message for Ajax Notices
	function teamUpMessage( classes, message ){
		$('#wpbody .wrap > h2').after('<div class="team-up-message notice notice-'+ classes +'"><p>'+ message +'</p></div>');
	}

	$('#reset-member-order').on('click', function(){
		if( confirm('Reset Member Order?') ){
			$('#the-list tr').each(function(i){
				var data = {
					'action': 'reset_team_member_order',
					'title': $(this).find('.title strong a').text(),
					'postID': $(this).attr('id').replace('post-', ''),
					'newPosition': ++i,
				};

				$.post(ajaxurl, data, function(response) {
					if( response.status == 400 || response.status == 403 ){
						alert( response.message );
						
						// Resume Dragging
						$table.removeClass('nodrag');
						$table.find('.team-up-draggable').addClass('column-team-up-draggable');
						$table.find('.team-up-draggable').removeClass('column-team-up-undraggable');
					} else {
						if( i == $('#the-list tr').length )
							alert( 'Member Order Reset!' );

						$table.removeClass('nodrag');
						$table.find('.team-up-draggable').addClass('column-team-up-draggable');
						$table.find('.team-up-draggable').removeClass('column-team-up-undraggable');
					}
				}, 'json');
			});
		}
	});

	// Color Picker Ajax (Fire after short delay, so it doesn't fire during color dragging)
	var timer;
	$('.color-field').wpColorPicker({
		defaultColor: '006191',
		change: function(event, ui){
			clearTimeout(timer);
			timer = setTimeout(function(){
				var	$field        = $(this),
				newValue          = ui.color.toString(),
				optionName        = $field.attr('name'),
				optionDisplayName = $field.closest('.team-up-option').find('.display-name').attr('aria-label').replace('Enable ', '');

				var data = {
					'action': 'modify_team_up_option',
					'optionName': optionName,
					'newValue': newValue,
					'optionDisplayName': optionDisplayName,
				};

				$.post(ajaxurl, data, function(response) {
					$('.team-up-message').remove(); // Prevent weird interaction with existing messages
					
					if( response.status == 200 ){
						classes = 'info';
					} else if( response.status == 400 || response.status == 403 ){
						classes = 'error';
					}

					teamUpMessage( classes, response.message );
				}, 'json');
			}.bind(this), 1000);
		}
	});

	// Ajax Toggle Global Options
	$('#team-up-options .team-up-checkbox').on( 'click', '.team-up-check', function(){
		var	$clicked     = $(this).closest('.team-up-checkbox'),
			currentState = $clicked.attr('data-attr'),
			optionName   = $clicked.find('input[type="checkbox"]').attr('name'),
			optionDisplayName = $clicked.find('.display-name').attr('aria-label').replace('Enable ', '');

		var data = {
			'action': 'toggle_team_up_option',
			'optionName': optionName,
			'currentState': currentState,
			'optionDisplayName': optionDisplayName,
		};

		$clicked.closest('.team-up-option').addClass('team-up-reloading');

		$.post(ajaxurl, data, function(response) {
			$('.team-up-message').remove(); // Prevent weird interaction with existing messages
			
			if( response.status == 200 ){
				classes = 'info';
			} else if( response.status == 400 || response.status == 403 ){
				classes = 'error';
			}

			$clicked.closest('.team-up-option').removeClass('team-up-reloading');
			$clicked.closest('.team-up-option').find('.team-up-value').text( response.newState );
			$clicked.attr('data-attr', response.newState );

			teamUpMessage( classes, response.message );
		}, 'json');
	});

	// Ajax Toggle on Post Meta
	$('.edit-php .team-up-checkbox').on( 'click', '.team-up-check', function(){
		var	$clicked     = $(this).closest('.team-up-checkbox'),
			currentState = $clicked.attr('data-state'),
			optionName   = $clicked.attr('data-option'),
			postID       = parseInt( $clicked.closest('tr').attr('id').replace('post-', '') );
			postTitle    = $clicked.closest('tr').find('td.column-title strong > a').text();

		var data = {
			'action': 'toggle_team_up_post_meta',
			'optionName': optionName,
			'currentState': currentState,
			'postTitle': postTitle,
			'postID': postID
		};

		$clicked.find('.team-up-check').addClass('team-up-reloading');

		$.post(ajaxurl, data, function(response) {
			$('.team-up-message').remove(); // Prevent weird interaction with existing messages
			
			if( response.status == 200 ){
				classes = 'info';
			} else if( response.status == 400 || response.status == 403 ){
				classes = 'error';
			}


			if( response.newState == 'disabled' ){
				$clicked.removeClass('enabled');
				$clicked.addClass('disabled');
			} else {
				$clicked.removeClass('disabled');
				$clicked.addClass('enabled');
			}

			$clicked.attr('data-state', response.newState );

			$clicked.find('.team-up-check').removeClass('team-up-reloading');
			
			teamUpMessage( classes, response.message );
		}, 'json');
	});

	// Drag and Drop on All Team Members List
	var $table = $('.edit-php.post-type-team-up #the-list');

	$table.tableDnD({
		dragHandle: '.column-team-up-draggable',
		onDragClass: "team-up-dragging",
		onDrop:	function(table,	row){
			$control = $(row).find('.team-up-draggable div');
			
			// Pause Dragging
			$table.addClass('nodrag');
			$table.find('.team-up-draggable').addClass('column-team-up-undraggable');
			$table.find('.team-up-draggable').removeClass('column-team-up-draggable');

			// Table Style Functions
			$table.find("tr").removeClass("even	odd");
			$table.find("tr:even").addClass("even");
			$table.find("tr:odd").addClass("odd");

			$(row).find('.column-title strong').append('<span class="team-up-loading"> - Loading…</span>');

			// Update New Order via AJAX
			var $prev = $(row).prev('tr');
			var $next = $(row).next('tr');

			var newPosition;
			var postID = $control.attr('data-id');
			var oldPosition = $control.attr('data-position');

			if( ! $prev[0] ){
				// No Previous Item - This is now first
				newPosition = 1;
			} else if( ! $next[0] ){
				// No Next Item - This is last (so take the Position of previous element)
				newPosition = parseInt( $prev.find('.team-up-draggable div').attr('data-position') );
			} else {
				// We're somewhere in the middle
				newPosition = parseInt( $prev.find('.team-up-draggable div').attr('data-position') ) + 1;
			}

			// Make Initial Ajax Request to Update Order
			var data = {
				'action': 'update_team_member_order',
				'postID': postID,
				'newPosition': newPosition,
				'oldPosition': oldPosition
			};

			$.post(ajaxurl, data, function(response) {
				if( response.status == 400 || response.status == 403 ){
					alert( 'Something went wrong. ' + response.message );
					
					// Resume Dragging
					$table.removeClass('nodrag');
					$table.find('.team-up-draggable').addClass('column-team-up-draggable');
					$table.find('.team-up-draggable').removeClass('column-team-up-undraggable');
				} else {
					i = 0;
					$table.find('tr').each(function(){
						$(this).find('.team-up-draggable div').attr('data-position', ++i );
						$(this).find('.team-up-draggable div span').text(i);
					});

					$('.team-up-loading').text(' - …Done!');
					setTimeout(function(){
						$('.team-up-loading').remove();
						
						// Resume Dragging
						$table.removeClass('nodrag');
						$table.find('.team-up-draggable').addClass('column-team-up-draggable');
						$table.find('.team-up-draggable').removeClass('column-team-up-undraggable');
					}, 1000 );
				}
			}, 'json');
		}
	});
});