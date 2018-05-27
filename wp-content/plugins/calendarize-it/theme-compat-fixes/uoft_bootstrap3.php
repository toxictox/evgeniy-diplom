<?php

/**
 * 
 *
 * @version $Id$
 * @copyright 2003 
 **/
function rhc_avada_bug_fix_theme_single_title( $title, $id=0 ){
	global $rhc_plugin;

	if( $rhc_plugin->template_frontend->is_taxonomy && $id==$rhc_plugin->template_frontend->taxonomy_template_page_id ){
		return $rhc_plugin->template_taxonomy_title;
	}else if( $rhc_plugin->template_frontend->is_event && $id==$rhc_plugin->template_frontend->event_template_id ){
		return get_the_title( $rhc_plugin->template_frontend->post_ID );	
	}else{
		return $title;
	}
}
add_filter('the_title', 'rhc_avada_bug_fix_theme_single_title', 10, 2);


if( '1' == $this->get_option( 'bug_fix_theme_single_title', '0', true )  ){
	$this->update_option( 'bug_fix_theme_single_title', '0' );
}

?>