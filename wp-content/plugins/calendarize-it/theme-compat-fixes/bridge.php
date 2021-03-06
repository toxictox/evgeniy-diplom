<?php

/**
 * 
 *
 * @version $Id$
 * @copyright 2003 
 **/

add_filter( 'the_title', 'rhc_bridge_the_title', 10, 4 );


function rhc_bridge_the_title( $title, $id ){

	global $rhc_plugin;
	if( 'version2'==$rhc_plugin->get_option('template_integration','',true) ){
		$template_page_id = intval( $rhc_plugin->get_option('event_template_page_id',0,true) );

		if( $template_page_id==$id ){
			global $post;
			if( is_object($post) && property_exists($post,'ID') && $post->ID!=$template_page_id ){
				return get_the_title( $post->ID );
			}
		}
	}
	
	
	return $title;
}

//WPML related fix:
add_filter( 'qode_title_text', 'rhc_qode_title_text', 10, 1);
function rhc_qode_title_text( $title ){
	global $rhc_plugin;
	
	if( $rhc_plugin->template_frontend->is_taxonomy ){
		return $rhc_plugin->template_frontend->term->name;
	}
		
	if( $rhc_plugin->template_frontend->is_event ){
		return get_the_title( $rhc_plugin->template_frontend->post_ID );
	}
	return $title;
}
?>