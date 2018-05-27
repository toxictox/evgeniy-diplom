<?php

if('plugin_righthere_calendar'==get_class($this)): 

	if( intval( $rhc_version ) == 0 ){

	}else{
		if (version_compare( $rhc_version, '4.3.7') > 0) {
			//previous version was already greater than 4.3.7, no need to do this again.
		}else{
			//previous version is 4.3.7 or lower, so we need to modify the upcoming event widget base id as it conflicts with jetpack.
		
			//changing widget base id from upcoming_events_widget to rhcoming_events_widget
			global $wpdb;
		
			$sql = "UPDATE {$wpdb->options} SET option_value=REPLACE( option_value, 'upcoming_events_widget', 'rhcoming_events_widget' ) WHERE option_name='sidebars_widgets';";
			$wpdb->query( $sql );
			
			
			$o = get_option( 'widget_upcoming_events_widget' );
			if( is_array( $o ) ){
				update_option( 'widget_rhcoming_events_widget', $o );
			}else{
			
			}
		}
	}


	//Description:  wp 4.4 finally implemented term meta data. so we are using that instead of our built in tax meta.
	global $wp_version;
	$version = substr($wp_version,0,3);	
	if( $version >= 4.4 ){
		$converted_taxmeta = intval( get_option('RHC_CONVERTED_TAXMETA',0) );
		if( 0==$converted_taxmeta ){		
			global $wpdb;
			$tables = $wpdb->get_results("show tables like '{$wpdb->prefix}taxonomymeta'");
			if (count($tables)){
				//there is a taxonometa table
				$tables2 = $wpdb->get_results("show tables like '{$wpdb->prefix}termmeta'");
				if (count($tables2)){
					update_option( 'RHC_CONVERTED_TAXMETA', 1 );

					//Copy data to new built in wp term meta data table
					$sql = "INSERT INTO `{$wpdb->termmeta}` (`term_id`,`meta_key`,`meta_value`) SELECT `taxonomy_id`,`meta_key`,`meta_value` FROM `{$wpdb->prefix}taxonomymeta`;";
					$wpdb->query( $sql );
				
					//Attempt to rename wp_taxonomymeta
					$sql = "RENAME table `{$wpdb->prefix}taxonomymeta` to `{$wpdb->prefix}taxonomymeta_bak`;";
					$wpdb->query( $sql );
				}
			}
		}		
	}

	//-- clear the built in cache
	//-- this needs rhc_plugin wich is not defined at this moment, so queue it for execution at plugins_loaded hook.	
	function rhc_update_plugins_loaded(){
		if(!function_exists('rhc_handle_delete_events_cache')){
			require_once RHC_PATH.'includes/function.rhc_handle_delete_events_cache.php';
		}
		rhc_handle_delete_events_cache();	
	}
	add_action('plugins_loaded', 'rhc_update_plugins_loaded');
	
	
endif;

?>