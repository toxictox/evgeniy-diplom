<?php

/**
 * 
 *
 * @version $Id$
 * @copyright 2003 
 **/
 
global $rhc_plugin;

if(defined('WPSEO_FILE')){
	include RHC_PATH.'plugin-compat-fixes/yoast-seo.php';
}

if( '1' == $rhc_plugin->get_option( 'bug_fix_theme_single_title', '0', true ) ){
	function rhc_bug_fix_theme_single_title( $title, $id=0 ){
		if( is_singular() ){
			global $post,$rhc_plugin;
			if( is_object( $post ) && property_exists( $post, 'post_type') && RHC_EVENTS==$post->post_type){
				$template_id = intval( $rhc_plugin->get_option( 'event_template_page_id', '', true ) );
				if( $template_id && $id==$template_id ){
					return $post->post_title;
				}
			}else if( is_object( $post ) && property_exists( $post, 'post_type') && 'page'==$post->post_type){
				if( false!==$rhc_plugin->template_taxonomy_title ){
					return $rhc_plugin->template_taxonomy_title;
				}
			}
		}
	
		return $title;
	}
	add_filter('the_title', 'rhc_bug_fix_theme_single_title', 10, 2);
}

if( '0' == $rhc_plugin->get_option( 'disable_calendar', '0', true ) ){
	// START Fixes related to plugins and themes using the 'get_calendar' filter hook.
	//-fix description: themes or plugins that are adding a get_calendar filter are breaking the
	// calendar taxonomy edit screen in latest wordpress versions. 
	add_action( 'current_screen', 'rhc_admin_bug_fix_get_calendar_filter', 99999 );
	function rhc_admin_bug_fix_get_calendar_filter(){
		global $wp_filter;
		$screen = get_current_screen();
		if( is_object( $screen ) && $screen->id == 'edit-calendar' ){
			if( isset( $wp_filter['get_calendar'] ) ){
				remove_all_filters('get_calendar');
				add_filter( 'get_calendar', 'rhc_bug_fix_get_calendar_filter', -999, 2 );
			}	
		}
	}

	//-- get_$taxonomy filter (fn get_term) conflict with the rhc calendar taxonomy.
	//-- there is a get_calendar filter that returns the wp calendar
	global $rhc_get_calendar_backup;
	$rhc_get_calendar_backup = false;
	add_filter( 'get_term', 'rhc_bug_fix_get_calendar_filter', 0, 2 );
	function rhc_bug_fix_get_calendar_filter( $term, $taxonomy ){
		if( $taxonomy==RHC_CALENDAR ){
			global $wp_filter;
			if( isset( $wp_filter['get_calendar'] ) ){
				if( is_object( $wp_filter['get_calendar'] ) ){
					$rhc_get_calendar_backup = clone $wp_filter['get_calendar']; 
				}else{
					$rhc_get_calendar_backup = $wp_filter['get_calendar']; 
				}
				
				if( class_exists('WP_Hook') ){
					$wp_filter['get_calendar'] = new WP_Hook();	
				}else{
					$wp_filter['get_calendar'] = array();	
				}
			
				add_filter( 'get_calendar', 'rhc_bug_fix_get_calendar_filter_2', 99999, 2 );
			}
		}
		return $term;
	}

	function rhc_bug_fix_get_calendar_filter_2( $term, $taxonomy ){	
		//return all get_calendar filters to position.
		global $wp_filter,$rhc_get_calendar_backup;

		if( false!==$rhc_get_calendar_backup && $taxonomy==RHC_CALENDAR ){		
			$wp_filter['get_calendar'] = $rhc_get_calendar_backup;
			remove_filter( 'get_calendar', 'rhc_bug_fix_get_calendar_filter_2', 99999, 2 );
		}
		return $term;
	}
	//END of get_calendar related fixes.
}


?>