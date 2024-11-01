<?php
	$files = glob( plugin_dir_path( __FILE__ ) . 'shortcodes/*.php', GLOB_BRACE );
	foreach( $files as $file ){
		include_once $file;
	}
?>