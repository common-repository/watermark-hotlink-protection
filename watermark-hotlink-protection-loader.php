<?php
/*
Plugin Name: Watermark Hotlink Protection
Plugin URI: http://MyWebsiteAdvisor.com/tools/wordpress-plugins/watermark-hotlink-protection/
Description: Adds watermark to images which have been hotlinked to be displayed on the websites which have hotlinked your images.
Version: 1.0
Author: MyWebsiteAdvisor
Author URI: http://MyWebsiteAdvisor.com
*/

register_activation_hook(__FILE__, 'watermark_hotlink_protection_activate');

register_deactivation_hook(__FILE__, 'watermark_hotlink_protection_deactivate');

register_uninstall_hook(__FILE__, "watermark_hotlink_protection_uninstall");



function watermark_hotlink_protection_uninstall(){
	
	delete_option('watermark-hotlink-protection-settings');
	delete_option('mywebsiteadvisor_pluigin_installer_menu_disable');

}


function watermark_hotlink_protection_deactivate() {
	
		$cache_dir_path = ABSPATH . "wp-content/hotlink-image-cache/";
		
		//clear image cache
		clear_watermark_hotlink_protection_cache($cache_dir_path);
		
		//clear htaccess modifications
		watermark_hotlink_protection_remove_markers(ABSPATH .'.htaccess');
}



function watermark_hotlink_protection_activate() {

	// display error message to users
	if ($_GET['action'] == 'error_scrape') {
		die("Sorry, Watermark Hotlink Protection Plugin requires PHP 5.0 or higher. Please deactivate Watermark Hotlink Protection Plugin.");
	}

	if ( version_compare( phpversion(), '5.0', '<' ) ) {
		trigger_error('', E_USER_ERROR);
	}
	
}



// require Watermark Hotlink Protection Plugin if PHP 5 installed
if ( version_compare( phpversion(), '5.0', '>=') ) {

	define('WHP_LOADER', __FILE__);

	include_once(dirname(__FILE__) . '/watermark-hotlink-protection-plugin-installer.php');
	
	require_once(dirname(__FILE__) . '/watermark-hotlink-protection-settings-page.php');
	require_once(dirname(__FILE__) . '/watermark-hotlink-protection-tools.php');
	require_once(dirname(__FILE__) . '/watermark-hotlink-protection-plugin.php');

	$watermark_hotlink_protection = new Watermark_Hotlink_Protection_Plugin();

}







function clear_watermark_hotlink_protection_cache($path){

    $path = rtrim($path, '/').'/';
    $handle = @opendir($path);
	if($handle){
		while(false !== ($file = readdir($handle))) {
			if($file != '.' and $file != '..' ) {
				$fullpath = $path.$file;
				if(is_dir($fullpath)) clear_watermark_hotlink_protection_cache($fullpath); else unlink($fullpath);
			}
		}
   	 	closedir($handle);
	}
    @rmdir($path);

}




//removes the rules and markers created by the insert_with_markers WP function
//based on the insert_with_markers() from wp-admin/includes/misc.php
function watermark_hotlink_protection_remove_markers($filename, $marker = "WatermarkHotlinkProtection"){
	
	if( is_writeable( $filename ) ) {
		
		$markerdata = explode( "\n", implode( '', file( $filename ) ) );
		
		if ( !$f = @fopen( $filename, 'w' ) )
			return false;	
		
		if($markerdata){
		
			$state = true;
		
			foreach($markerdata as $n => $markerline ){	
			
				if (strpos($markerline, '# BEGIN ' . $marker) !== false)
					$state = false;
					
				if ( $state ) {
					if ( $n + 1 < count( $markerdata ) )
						fwrite( $f, "{$markerline}\n" );
					else
						fwrite( $f, "{$markerline}" );
				}
				
				if (strpos($markerline, '# END ' . $marker) !== false) 
					$state = true;
				
			}
		}
		
		fclose( $f );
		return true;
		
	}else{
		return false;	
	}
}
?>