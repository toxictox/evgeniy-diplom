<?php


add_filter( 'wpex_post_id', 'rhc_wpex_post_id', 10, 1 );

function rhc_wpex_post_id( $id ){
	
	global $rhc_plugin,$post;
		if(is_single() && $post->post_type==RHC_EVENTS){
			$template_page_id = intval($rhc_plugin->get_option('event_template_page_id',0,true));
			$template_page_id = apply_filters( 'rhc_event_template_page_id', $template_page_id );
		
			return $template_page_id;
		}	
	return $id;
} 