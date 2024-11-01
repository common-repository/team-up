<?php
	#error_reporting(E_ALL);
	#ini_set('display_errors',1);

	$options = array(
		'square_theme',
		'hide_department',
		'force_genesis_full_width'
	);

	foreach( $options as $option ){
		if( filter_var( get_option( "_team_up_$option" ), FILTER_VALIDATE_BOOLEAN ) ){
			${$option.'_checked'} = 'checked="checked"';
			${$option.'_enabled'} = 'enabled';
		} else {
			${$option.'_checked'} = '';
			${$option.'_enabled'} = 'disabled';
		}
	}
?>
<div class="wrap">
	<h2>Team Up Options</h2>
	<div id="team-up-options" class="team-up-admin-panel">
		<h3>Layout & Theme Options</h3>
		<div class="team-up-option">
			<label class="team-up-checkbox" for="_team_up_square_theme" data-attr="<?php echo $square_theme_enabled; ?>">
				<span class="display-name" aria-label="Enable Square Theme"></span>
				<input type="checkbox" name="_team_up_square_theme" id="_team_up_square_theme" <?php echo $square_theme_checked; ?> />
				<span class="team-up-check">
					<span class="team-up-check_ajax">
						<?php echo Team_Up::display_svg( 'checkmark', 'icon' ); ?>
					</span>
				</span>
			</label>
			<span class="team-up-option_label">Square (Large Image) Theme is <strong class="team-up-value"><?php echo ucwords( $square_theme_enabled ); ?></strong></span>
		</div>
		<br />
		<div class="team-up-option">
			<label class="team-up-checkbox" for="_team_up_hide_department" data-attr="<?php echo $hide_department_enabled; ?>">
				<span class="display-name" aria-label="Enable Hide Department"></span>
				<input type="checkbox" name="_team_up_hide_department" id="_team_up_hide_department" <?php echo $hide_department_checked; ?> />
				<span class="team-up-check">
					<span class="team-up-check_ajax">
						<?php echo Team_Up::display_svg( 'checkmark', 'icon' ); ?>
					</span>
				</span>
			</label>
			<span class="team-up-option_label">Hide Department is <strong class="team-up-value"><?php echo ucwords( $hide_department_enabled ); ?></strong></span>
		</div>
		<br />
		<div class="team-up-option">
			<p><strong>Accent Color:</strong></p>
			<label class="team-up-color" for="_team_up_accent_color">
				<span class="display-name" aria-label="Accent Color">Accent Color</span>
				<input type="text" name="_team_up_accent_color" id="_team_up_accent_color" class="color-field" data-default-color="#006191" value="<?php echo ( $color = get_option( '_team_up_accent_color' ) ) ? $color : '#006191'; ?>" />
			</label>
		</div>
		<br>
		<?php if( function_exists( 'genesis' ) ){ ?>
			<div class="team-up-option">
				<label class="team-up-checkbox" for="_team_up_force_genesis_full_width" data-attr="<?php echo $force_genesis_full_width_enabled; ?>">
					<span class="display-name" aria-label="Force Genesis Full Page"></span>
					<input type="checkbox" name="_team_up_force_genesis_full_width" id="_team_up_force_genesis_full_width" <?php echo $force_genesis_full_width_checked; ?> />
					<span class="team-up-check">
						<span class="team-up-check_ajax">
							<?php echo Team_Up::display_svg( 'checkmark', 'icon' ); ?>
						</span>
					</span>
				</label>
				<span class="team-up-option_label">Force Genesis Full Width Layout is <strong class="team-up-value"><?php echo ucwords( $force_genesis_full_width_enabled ); ?></strong></span>
			</div>
		<?php } ?>
	</div>
</div>