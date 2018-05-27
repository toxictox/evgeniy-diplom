<?php

/**
 * 
 *
 * @version $Id$
 * @copyright 2003 
 **/
function rhc_tennisclub_filter_get_blog_title($a, $b){

	global $rhc_plugin;
	
	if( $rhc_plugin->template_frontend->is_taxonomy ){
		return $rhc_plugin->template_taxonomy_title;
	}

	return $a;
}
add_filter('tennisclub_filter_get_blog_title','rhc_tennisclub_filter_get_blog_title',10,2)

?>