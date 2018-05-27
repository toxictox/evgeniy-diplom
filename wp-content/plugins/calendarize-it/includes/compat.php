<?php

/**
 * 
 *
 * @version $Id$
 * @copyright 2003 
 **/
 
/* WPML */ 
//add_filter('icl_set_current_language','rhc_icl_set_current_language');
function rhc_icl_set_current_language($lang){
	if(isset($_REQUEST['rhc_action'])&&isset($_REQUEST['lang']))return $_REQUEST['lang'];
	return $lang;
}


/*
WPML version 3.3.6, the translated event links to the template not the event.
*/
add_filter('icl_ls_languages','rhc_icl_ls_languages');
function rhc_icl_ls_languages( $languages ){
	global $rhc_plugin;
	if( $rhc_plugin->template_frontend && $rhc_plugin->template_frontend->is_event ){
		global $sitepress;
		$trid         = $sitepress->get_element_trid( $rhc_plugin->template_frontend->post_ID, 'post_page' );
		$translations = $sitepress->get_element_translations( $trid, 'post_page' );
		foreach( $languages as $code => $lang ){
			$url = get_permalink( $translations[$code]->element_id );
			$url = $sitepress->convert_url( $url, $code );
			
			$languages[$code]['url'] = $url;
		}	
	}
	return $languages;
}

add_filter('rhc_event_template_page_id', 'wpml_rhc_event_template_page_id',10,1);
function wpml_rhc_event_template_page_id( $event_template_page_id ){
	if( function_exists('icl_object_id') ){
		return icl_object_id( $event_template_page_id, 'page', true);
	}
	
	return $event_template_page_id;
}
?>