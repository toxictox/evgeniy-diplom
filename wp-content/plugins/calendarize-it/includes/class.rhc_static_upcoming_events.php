<?php

class rhc_static_upcoming_events {
	var $uid=0;
	var $in_tax_loop = false;
	var $in_tax_loop_post_id = false;
	var $in_dbox = 0;
	function __construct(){
		add_shortcode('rhc_static_upcoming_events', array(&$this,'sc_rhc_static_upcoming_events'));
		//--- multiple venues gmap
		add_shortcode( 'rhc_gmap', 		array(&$this,'handle_rhc_gmap'));	
		//---- main template handler	
		add_shortcode('rhc_event_details', 	array(&$this,'rhc_event_details'));
		//---- taxonomy loops
		add_shortcode('rhc_tax_loop', 		array(&$this,'rhc_tax_loop'));		
		add_shortcode('rhc_venue_loop', 	array(&$this,'rhc_tax_loop'));		
		add_shortcode('rhc_organizer_loop', array(&$this,'rhc_tax_loop'));		
		add_shortcode('rhc_calendar_loop', array(&$this,'rhc_tax_loop'));	
		//---- term	
		add_shortcode('rhc_term',	 		array(&$this,'rhc_term'));	
		add_shortcode('rhc_venue_term',	 	array(&$this,'rhc_term'));	
		add_shortcode('rhc_organizer_term',	 array(&$this,'rhc_term'));	
		add_shortcode('rhc_calendar_term',	 array(&$this,'rhc_term'));	
		//---- venue meta
		add_shortcode('rhc_venue_meta',	 	array(&$this,'rhc_term_meta'));
		add_shortcode('rhc_organizer_meta',	array(&$this,'rhc_term_meta'));	
		//---- cells for rhc_detailbox
		add_shortcode('rhc_venue_meta_info_cell',		array(&$this,'rhc_venue_meta_info_cell'));	
		add_shortcode('rhc_organizer_meta_info_cell',	array(&$this,'rhc_organizer_meta_info_cell'));	
		add_shortcode('rhc_venue_image',				array(&$this,'rhc_venue_image'));	
		add_shortcode('rhc_venue_website',				array(&$this,'rhc_venue_website'));	
		add_shortcode('rhc_organizer_image',			array(&$this,'rhc_organizer_image'));	
		add_shortcode('rhc_organizer_website',			array(&$this,'rhc_organizer_website'));	
		
		//--- event
		add_shortcode('rhc_title', array( &$this, 'rhc_title') );
		add_shortcode('rhc_description', array( &$this, 'rhc_description') );
		add_shortcode('rhc_label', array( &$this, 'rhc_label') );
		add_shortcode( 'rhc_postmeta', array( &$this, 'rhc_postmeta' ) );
		//---
		foreach( array('rhc_date', 'rhc_start','rhc_start_date','rhc_start_time','rhc_end','rhc_end_date','rhc_end_time') as $shortcode){
			add_shortcode($shortcode, 		array(&$this,'handle_rhc_date'));
		}
		
		add_shortcode( 'rhc_dbox', 		array(&$this,'handle_rhc_dbox'));
		add_shortcode( 'rhc_dbox_cell', array(&$this,'handle_rhc_dbox_cell'));	
		
		//$this->handle_post_rhc_set_browser_offset();	
	}
	
	function handle_post_rhc_set_browser_offset(){
		//used by the local_tz argument of the static events sc.
		if( isset( $_POST['rhc_set_browser_offset'] ) ){
			//handle save cookie.
			if( is_numeric( $_POST['rhc_set_browser_offset'] ) ){
				//ob_start();	
				setcookie('rhc_tz', $_POST['rhc_set_browser_offset'],  time()+86400, '/' );
				$_COOKIE['rhc_tz']= $_POST['rhc_set_browser_offset'];
				//ob_end_clean();//avoid a php warning about headers.
			}
		}	

		if( isset( $_REQUEST['reset_rhc_tz'] ) ){

			setcookie('rhc_tz', null,  -1, '/' );
			/*
			echo "<pre>";
			print_r( $_COOKIE );
			echo "</pre>";
			die('debug');
			*/
		}		
	}
	
	function sc_output_conditions_met ( $atts, $content=null, $code="" ){
		extract(shortcode_atts(array(
			'conditional_tag' 		=> '',
			'capability'	 		=> '',
			'meta_key'				=> '',
			'meta_value'			=> '',
			'meta_value_default'	=> '', //value to give meta_value if it is empty.
			'usermeta_key'			=> '', //a usermeta to test against,
			'usermeta_value'		=> '',
			'usermeta_default'		=> ''			
		), $atts));
		
		//---
		if( !empty( $capability ) ){
			if( !current_user_can( $capability ) ){
				return false;
			}
		}	
		
		//---------test wp conditional tags
		if(''!=trim($conditional_tag)){
			$allowed_conditional_tags = apply_filters( 'shortcode_allowed_conditional_tags', array( 
				'is_home',
				'is_front_page',
				'is_singular',
				'is_page',
				'is_single',
				'is_sticky',
				'is_category',
				'is_tax',
				'is_author',
				'is_archive',
				'is_search',
				'is_attachment',
				'is_tag',
				'is_date',
				'is_paged',
				'is_main_query',
				'is_feed',
				'is_trackback',
				'in_the_loop',
				'is_user_logged_in'
				), $code );
			
			$test_tags = explode(',',trim(str_replace(' ','',$conditional_tag)));
			if(is_array($test_tags) && count($test_tags)>0){
				$condition_matched = false;
				foreach($test_tags as $test_method){
					if( in_array($test_method,$allowed_conditional_tags) && $test_method() ){
						$condition_matched = true;
						break;
					}
				}
				if(false===$condition_matched){
					return false;
				}
			}
		}	
		
		//-------- test for post meta_key conditional value 
		if($meta_key!=''){
			global $post; //to be used in a loop where $post is defined.
			$post_ID = property_exists($post,'ID') ? $post->ID : false;

			if(false!==$post_ID){
				$value = get_post_meta($post_ID,$meta_key,true);
				$value = ''==$value?$meta_value_default:$value;		
				//TODO: allow other operators
				if( $value != $meta_value ){
					//condition was not matched.
					return false;
				}
			}		
		}
		//--------
		
		//--------- test for usermeta
		if($usermeta_key!=''){
			if( is_user_logged_in() ){
				global $userdata;
				$value = get_user_meta( $userdata->ID, $usermeta_key, true );
				$value = ''==$value?$usermeta_default:$value;	
				if( $value != $usermeta_value ){
					//condition was not matched.
					return false;
				}				
			}else{
				//user not logged, condition not matched.
				return false;
			}
		}
		//---------		
		
		return true;
	}

	function rhc_venue_image( $atts, $format=null, $code="" ){
		$atts = (array)$atts;
		$atts['class']='fe-image-holder';
		$atts['meta_fields']='image';
		$atts['term_meta_template'] = '<div %1$s></div>';
		return $this->rhc_term_meta( $atts, '<img src="%1$s"/>', 'rhc_venue_meta' );
	}
	
	function rhc_venue_website( $atts, $format=null, $code="" ){
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'label' 				=> __('Website','rhc')
		), $atts));	
		$atts['meta_fields']='website,websitelabel';
		$raw = isset( $atts['raw'] ) && '1'==$atts['raw'] ? true : false ;
		if( $raw ){
			$format = '%1$s';
		}else{
			$link_target = isset( $atts['link_target'] ) ? $atts['link_target'] : '_blank';
			$format = '<a href="%1$s" target="' . $link_target . '">%2$s</a>';
		}
		if( !empty($label) || $this->in_dbox ){
			$format = sprintf('<div class="rhc-info-cell %s"><span class="fe-extrainfo-label">%s</span><span class="fe-extrainfo-value">%s</span></div>',
				( empty($label) ? 'fe-is-empty-label-1' : 'fe-is-empty-label-0' ),
				$label,
				$format
			);
		}
		return $this->rhc_term_meta( $atts, $format, 'rhc_venue_meta' );
	}
	
	function rhc_organizer_image( $atts, $format=null, $code="" ){
		$atts = (array)$atts;
		$atts['class']='fe-image-holder';
		$atts['meta_fields']='image';
		$atts['term_meta_template'] = '<div %1$s></div>';
		return $this->rhc_term_meta( $atts, '<img src="%1$s"/>', 'rhc_organizer_meta' );
	}
	
	function rhc_organizer_website( $atts, $format=null, $code="" ){
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'label' 				=> __('Website','rhc')
		), $atts));	
		$atts['meta_fields']='website,websitelabel';
		$raw = isset( $atts['raw'] ) && '1'==$atts['raw'] ? true : false ;
		if( $raw ){
			$format = '%1$s';
		}else{
			$link_target = isset( $atts['link_target'] ) ? $atts['link_target'] : '_blank';
			$format = '<a href="%1$s" target="' . $link_target . '">%2$s</a>';
		}
		if( !empty($label) || $this->in_dbox ){
			$format = sprintf('<div class="rhc-info-cell %s"><span class="fe-extrainfo-label">%s</span><span class="fe-extrainfo-value">%s</span></div>',
				( empty($label) ? 'fe-is-empty-label-1' : 'fe-is-empty-label-0' ),
				$label,
				$format
			);
		}
		return $this->rhc_term_meta( $atts, $format, 'rhc_organizer_meta' );
	}
	
	function rhc_venue_meta_info_cell( $atts, $format=null, $code="" ){
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'label'				=> '',
			'class'				=> 'rhc-venuebox-row rhc-info-cell',
			'term_meta_template'=> '<div %1$s></div>',
			'raw'				=> ''
		), $atts));		//meant to be used inside the custom dbox ( rhc_detailbox shortcode ).	
		
		if( intval( $raw ) ){
			$format = '%1$s';
		}else{
			$label = htmlentities($label) ; 
			$format = '';
			if( !empty( $label ) ){
				$format = '<span class="fe-extrainfo-label">' . $label . '</span>';
			}
			$format .= '<span class="fe-extrainfo-value">%1$s</span>';
			$class .= ' ' . ( empty($label) ? 'fe-is-empty-label-1' : 'fe-is-empty-label-0' );
			if( $this->in_dbox ){
				$format = sprintf('<div class="%s">%s</div>',
					$class,
					$format
				);
			}
		}

		return $this->rhc_term_meta( $atts, $format, 'rhc_venue_meta' );
	}

	function rhc_organizer_meta_info_cell( $atts, $format=null, $code="" ){
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'label'				=> '',
			'class'				=> 'rhc-organizer-row rhc-info-cell',
			'term_meta_template'=> '<div %1$s></div>',
			'raw'				=> ''
		), $atts));		
		
		if( intval( $raw ) ){
			$format = '%1$s';
		}else{
			$label = htmlentities($label) ; 
			$format = '';
			if( !empty( $label ) ){
				$format = '<span class="fe-extrainfo-label">' . $label . '</span>';
			}
			$format .= '<span class="fe-extrainfo-value">%1$s</span>';
			$class .= ' ' . ( empty($label) ? 'fe-is-empty-label-1' : 'fe-is-empty-label-0' );
			if( $this->in_dbox ){
				$format = sprintf('<div class="%s">%s</div>',
					$class,
					$format
				);
			}
		}
		
		return $this->rhc_term_meta( $atts, $format, 'rhc_organizer_meta' );
	}

	function rhc_term_meta( $atts, $format=null, $code="" ){
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'taxonomy'			=> '',
			'field'				=> '',
			'meta_fields'		=> '',//overwrited by field value if set.
			'class'				=> '',
			'holder_tag'		=> '',
			'term_meta_template'=> '<span %1$s></span>',
			'rnoe'				=> '0'
		), $atts));
		
		switch ( $code ){
			case 'rhc_organizer_meta':
				$taxonomy = RHC_ORGANIZER;
				breaK;
			case 'rhc_venue_meta':
				$taxonomy = RHC_VENUE;
				break;
			default:
				$taxonomy = empty( $taxonomy ) ? false :  $taxonomy;
		}
		
		if( empty( $meta_fields ) ){
			$meta_fields = $field;
		}
		
		$meta_fields = empty( $meta_fields ) ?  false : $meta_fields ;
		if( false===$taxonomy || false===$meta_fields ){
			return '';
		}
		
		if( !empty($holder_tag) ){
			$term_meta_template = sprintf( '<%1$s%2$s></%1$s>', $holder_tag, '%1$s' );
		}
		
		if( empty($format) ){
			$arr = explode(',',$meta_fields);
			$brr = array();
			foreach($arr as $field){
				$brr[]='%s';
			}
			$format = implode(', ',$brr);
		}
	
		$attributes = array(
			sprintf(' data-tterm_meta="%s"', $taxonomy),
			sprintf(' data-fields="%s"', $meta_fields),
			( empty( $class ) ? '' : sprintf(' class="%s"', $class )),
			( empty( $format ) ? '' : sprintf(' data-format="%s"', esc_attr($format))),
			( 0==intval($rnoe) ) ? '' : ' data-rnoe="rnoe"'
		);
		
		$content = sprintf( $term_meta_template,
			implode(' ', $attributes),
			$class
		);
//file_put_contents( ABSPATH.'api.log', time().$content );
		$output = $this->rhc_event_details( $atts, $content, $code );
					
		return 	$output;
	}

	function rhc_term( $atts, $content=null, $code="" ){
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'label' 				=> ''
		), $atts));	
		$enable_link = isset( $atts['enable_link'] ) ? intval( $atts['enable_link'] ) : true ;
		
		switch ( $code ){
			case 'rhc_organizer_term':
				$label = empty( $label ) ? __('Organizer','rhc') : $label ;
				$icon_class = 'rhc-icon-organizer';
				$taxonomy = RHC_ORGANIZER;
				breaK;
			case 'rhc_venue_term':
				$label = empty( $label ) ? __('Venue','rhc') : $label ;
				$icon_class = 'rhc-icon-location';
				$taxonomy = RHC_VENUE;
				break;
			case 'rhc_calendar_term':
				$label = empty( $label ) ? __('Calendar','rhc') : $label ;
				$icon_class = 'rhc-icon-location';
				$taxonomy = RHC_CALENDAR;
				break;
			default:
				$taxonomy = isset( $atts['taxonomy'] ) ? $atts['taxonomy'] : false;
				$icon_class = 'rhc-icon-'.$taxonomy;
		}

		if( false===$taxonomy ){
			return '';
		}

		$rnoe = 'data-rnoe="rnoe"';
		$rnoe = '';
		if( $enable_link ){
			$content = sprintf('<div class="rhc-term-%1$s" data-tterm="%1$s" data-fields="name,term_link" data-format="%4$s" data-empty_fields="term_link" data-empty_format="%5$s" %3$s></div>',
				$taxonomy,
				'',
				$rnoe,
				esc_attr('<a href="%2$s"><span class="'. $icon_class .'"></span><span>%1$s</span></a>'),
				esc_attr('<span class="rhc-icon-location"></span><span>%1$s</span>')
			);		
		}else{
			$content = sprintf('<div class="rhc-term-%1$s" data-tterm="%1$s" data-fields="name" data-format="%3$s" %2$s></div>',
				$taxonomy,
				$rnoe,
				esc_attr('<span class="'. $icon_class .'"></span><span>%1$s</span>')
			);
		}
		if( !empty($label) || $this->in_dbox ){
			$content = sprintf('<div class="rhc-info-cell %s"><span class="fe-extrainfo-label">%s</span><span class="fe-extrainfo-value">%s</span></div>',
				( empty($label) ? 'fe-is-empty-label-1' : 'fe-is-empty-label-0' ),
				$label,
				$content
			);
		}
		return $this->rhc_event_details( $atts, $content, $code );
	}

	function rhc_tax_loop( $atts, $content=null, $code="" ){
		$atts = (array)$atts;
		switch ( $code ){
			case 'rhc_organizer_loop':
				$taxonomy = RHC_ORGANIZER;
				break;
			case 'rhc_venue_loop':
				$taxonomy = RHC_VENUE;
				break;
			case 'rhc_calendar_loop':
				$taxonomy = RHC_CALENDAR;
				break;
			default:
				$taxonomy = isset( $atts['taxonomy'] ) ? $atts['taxonomy'] : false;
		}
	
		if( false===$taxonomy ){
			return '';
		}
	
		$rnoe = 'data-rnoe="rnoe"';
		$rnoe = '';
		$this->in_tax_loop = true; //signal the upcoming do_shortcode so that child term elements do not render, only output template.
		if( isset( $atts['post_id'] ) ){
			$this->in_tax_loop_post_id = intval($atts['post_id']);
		}
		$content = sprintf('<div class="rhc-%1$s-details %4$s" data-tterm_loop="%1$s" %3$s>%2$s</div>',
			$taxonomy,
			do_shortcode( $content ), //render child before passing it to the renderer.
			$rnoe,
			( isset($atts['class']) ? $atts['class'] : '' )
		);
		$this->in_tax_loop = false;
		$this->in_tax_loop_post_id = false;
		
		$output = $this->rhc_event_details( $atts, $content, $code );
		if( isset( $atts['container_class'] ) && !empty( $atts['container_class'] ) ){
			$output = sprintf('<div class="%s">%s</div>',
				$atts['container_class'],
				$output
			);
		}
		
		return $output;
	}

	function rhc_event_details( $atts, $content=null, $code="" ){
		$atts = (array)$atts;
		$this->set_params_for_rhc_event_details( $atts, $content, $code );		
		if( $this->in_tax_loop ){
			return do_shortcode( $content );
		}
		return $this->sc_rhc_static_upcoming_events( $atts, $content, $code );
	}

	function set_params_for_rhc_event_details( &$atts, $content, $code ){
		$atts = (array)$atts;
		$atts['post_id'] 				= isset( $atts['post_id'] ) ? $atts['post_id'] : 'current' ;
		$atts['page'] 					= '0';
		$atts['number'] 				= '1';
		$atts['parse_taxonomy']			= '1';
		$atts['parse_taxonomymeta']		= '1';
		$atts['microdata']				= '0';
		$atts['holder']					= '0';
		$atts['last_event_info']		= '0';
		$atts['feed']					= '0';
		if( !isset( $atts['parse_postmeta'] ) || empty( $atts['parse_postmeta'] ) ){
			$atts['parse_postmeta']			= 'fc_color,fc_text_color';
		}else{
			$atts['parse_postmeta']			.= ',fc_color,fc_text_color';
		}
		
		$atts['custom_content_wrapper'] = '<div class"sc-tax-loop">%s</div>';
		
		foreach( array('taxonomy','terms','date','date_end','post_type') as $field ){
			$atts[$field]		= '';
		}
	}


	function handle_rhc_gmap($atts, $content='', $code="" ){
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'type' 				=> 'interactive',
			'gmap_taxonomy'		=> RHC_VENUE,
			'width'				=> '300',
			'height'			=> '150',
			'zoom'				=> '15',
			'maptype'			=> 'ROADMAP',
			'ratio'				=> '4:3',
			'rnoe'				=> '1',
			'class'				=> '',
			'init_class'		=> 'rhc-sc-gmap',
			'holder_class'		=> 'rhc-gmap-holder',
			'single_marker'		=> ''//if set to 1 if rendered inside a venue loop, only render the corresponding marker.
		), $atts));

		$atts['post_id'] 				= isset( $atts['post_id'] ) ? $atts['post_id'] : 'current' ;
		$atts['page'] 					= '0';
		$atts['number'] 				= '1';
		$atts['parse_postmeta']			= 'fc_color,fc_text_color';
		$atts['parse_taxonomy']			= '1';
		$atts['parse_taxonomymeta']		= '1';
		$atts['microdata']				= '0';
		$atts['holder']					= '0';
		$atts['last_event_info']		= '0';
		$atts['feed']					= '0';
		
		$atts['js_init_script'] = sprintf('jQuery(document).ready(function($){$(".%s").rhcGmap({});});',$init_class);	
		
		foreach( array('taxonomy','terms','date','date_end','post_type') as $field ){
			$atts[$field]		= '';
		}	
		
		if( $this->in_tax_loop && false!==$this->in_tax_loop_post_id ){
			$atts['post_id'] = $this->in_tax_loop_post_id;
		}
		
		if( ''==trim($single_marker) && $this->in_tax_loop ){
			$single_marker = '1';
		}
		
		//--- template
		$class = empty( $class ) ? $init_class : $class.' '.$init_class;
		
		$data = array();
		$data[] = sprintf('data-size="%s"',
			sprintf( '%sx%s', intval($width), intval($height) )
		);
		
		foreach( array( 'type', 'gmap_taxonomy', 'zoom', 'maptype', 'ratio', 'rnoe', 'single_marker') as $field ){
			if( !empty( $$field ) ){
				$data[] = sprintf('data-%s="%s"',
					$field,
					$$field
				);	
			}
		}
		
		$content.= sprintf('<div class="%s"><div class="rhc-gmap %s" %s></div></div>',
			$holder_class,
			$class,
			implode( ' ', $data )
		);

		return $this->sc_rhc_static_upcoming_events( $atts, $content, $code );
	}	
	
	function rhc_title( $atts, $content='', $code="" ){
		$atts = (array)$atts;
		$content = '<div class="rhc-info-cell fe-cell-label fe-is-empty-1 fe-is-empty-label-0"><span class="fe-extrainfo-label rhc-title"></span></div>';
		return $this->rhc_event_details( $atts, $content, $code );
	}

	function rhc_description( $atts, $content='', $code="" ){
		$atts = (array)$atts;
		$content = '<div class="rhc-info-cell fe-is-empty-label-0"><span class="fe-extrainfo-value rhc-description"></span></div>';
		return $this->rhc_event_details( $atts, $content, $code );
	}

	function rhc_label( $atts, $content='', $code="" ){
		$atts = (array)$atts;
		if( !isset( $atts['label'] ) ){
			return '';
		}
		$content = sprintf('<div class="rhc-info-cell fe-cell-label fe-is-empty-1 fe-is-empty-label-0"><span class="fe-extrainfo-label">%s</span></div>',
			$atts['label']
		);
		return $this->rhc_event_details( $atts, $content, $code );
	}

	function rhc_postmeta( $atts, $format='', $code="" ){
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'label'				=> '',
			'postmeta_fields' 	=> '',
			'postmeta_format'	=> '%1$s',
			'class'				=> 'rhc-info-cell',
			'rnoe'				=> '1',
			'postmeta_template'	=> '<span %1$s></span>'
		), $atts));	
		
		$atts['parse_postmeta'] = $postmeta_fields;
		
		if( !$this->sc_output_conditions_met ( $atts, $format, $code) ){
			return '';
		}		

		$label = htmlentities($label) ; 
		$format = '';
		if( !empty( $label ) ){
			$format = '<span class="fe-extrainfo-label">' . $label . '</span>';
		}
		$format .= sprintf('<span class="fe-extrainfo-value">%s</span>', $postmeta_format);
	
		$class .= ' ' . ( empty($label) ? 'fe-is-empty-label-1' : 'fe-is-empty-label-0' );
		if( $this->in_dbox ){
			$format = sprintf('<div class="%s">%s</div>',
				$class,
				$format
			);
		}
		
		$attributes = array(
			sprintf('data-postmeta_fields="%s"', $postmeta_fields ),
			sprintf('data-postmeta_format="%s"', esc_attr($format) ),
			( 0==intval($rnoe) ) ? '' : 'data-rnoe="rnoe"'
		);		
		
		$content = sprintf( $postmeta_template,
			implode(' ', $attributes),
			$class
		);
//file_put_contents( ABSPATH.'api.log', time().$content.print_r($atts,true) );

		$output = $this->rhc_event_details( $atts, $content, $code );		

		return $output;
	}	
	
	function handle_date_today( $atts ){
		if( isset( $atts['date'] ) && strtolower( $atts['date'] ) == 'today'  ){
			$ts = strtotime( current_time('mysql') );	
			
			$atts['date'] 		= date( 'Y-m-d 00:00:00', $ts );
			$atts['date_end'] 	= date( 'Y-m-d 23:59:59', $ts );		
		}
		return $atts;
	}
	
	function sc_rhc_static_upcoming_events($atts,$content=null,$code=""){
		$atts = (array)$atts;
		global $rhc_plugin;
		$uid = ++$this->uid;
		$atts = $this->handle_date_today( $atts );
		$atts = apply_filters( 'supe_atts', $atts, $uid );
		//--
		if( !$this->sc_output_conditions_met ( $atts, $content, $code) ){
			return '';
		}
		//--
		
		//-- NOTE: Important! when adding parameters here, also add them to calendar_ajax::supe_get_events, also add it to the compact function below for this::get_events
		$shortcode_atts = shortcode_atts(array(
			'uid'		=> $uid,
			'test' 		=> '',
			'page' 		=> '0',
			'number'	=> '5',
			'taxonomy'	=> '',
			'terms'		=> '',
			'terms_children'	=> '',
			'template'	=> 'widget_upcoming_events.php',
			'class'		=> 'rhc_supe_holder',
			'prefix'			=> 'uew',//not really used.
			'parse_postmeta' => '',//comma separated fields to include in the event ovent as a meta array().
			'parse_taxonomy'	=> '0',
			'parse_taxonomymeta'=> '1',
			'order'		=> 'ASC',
			'date'		=> 'NOW',
			'date_end'	=> '',
			'date_compare'	=> '',
			'date_end_compare'	=> '',
			'horizon'	=> 'hour',
			'allday'	=> '', //empty for any, 1 for allday only, 0 for non-allday only.
			'no_events_message' => '',
			'post_status' => 'publish',
			'post_type'	=> '',
			'author'	=> '',
			'author_current' => '',//for vc.
			'do_shortcode' => '1',
			'the_content'  => '0',
			'separator' => '',
			'holder'	=> '1',
			'dayspast'	=> '',  //for compat with upcoming evengts widget
			'premiere'		=> '0',
			'auto'			=> '0',
			'hideempty'		=> '0',
			'feed'			=> '',
			'words'			=> '',
			'render_images' => '',
			'calendar_url'	=> '',
			'loading_overlay'		=> '0',
			'for_sidebar' 	=> '0',
			'post_id'		=> '',
			'current_post'	=> '',//for use inside a loop
			'rdate'	=> '',//for use with post_ID to query a specific event recurring instance.			
			'js_init_script' => '',
			'vc_js_init_script' => '', //VC not really to be passed in the shortcode. the addon will fill this with its corresponing sequence - without script tags.
			'nav'			=> '',
			'tax_and_filter'=> '',
			'header'		=> '',
			'hierarchical_filter' => '0',
			'terms_hide_empty' => '0',
			'tax_filter_multiple' => '1',
			'geo_radius'	=> '',
			'geo_center'	=> '',
			'local_tz'		=> '', //client side convert to local timezone.
			'tax_and_filtering'	=> '0' //flags the js that and filter should be used
		), $atts);
	
		$tz_client_server_difference = 0;
		if( 1==intval( $shortcode_atts['local_tz'] ) ){
			/*
			echo "<pre>";
			print_r( $_COOKIE );
			echo "</pre>";
			return 'DONE';
			*/
			/*
			$wp_gmt_offset = get_option('gmt_offset');
			$browser_offset = false;
			
			if( isset( $_POST['rhc_set_browser_offset'] ) ){				
				$browser_offset = $_POST['rhc_set_browser_offset'];
			}else{
				if( !isset( $_COOKIE['rhc_tz'] ) ){
					//submit client browser timezone.
					echo "<script>";
					echo 'var form = document.createElement("form");var element1 = document.createElement("input");';
					echo 'form.method = "POST";form.action = "";';
					echo 'element1.name="rhc_set_browser_offset";element1.value=((new Date()).getTimezoneOffset()/60);';
					echo 'form.appendChild(element1);document.body.appendChild(form);form.submit();';
					echo "</script>";	
					die();
				}			
				
				$browser_offset = $_COOKIE['rhc_tz'];
			}
			
			$tz_client_server_difference = $wp_gmt_offset + $browser_offset;
			
			if( isset( $_REQUEST['rhc_debug'] ) && current_user_can('manage_options') ){
				echo "wp_gmt_offset $wp_gmt_offset<br>";
				echo "browser_offset $browser_offset<br>";
				echo "tz_client_server_difference $tz_client_server_difference<br>";			
			}
			*/
		}
		
		if( ''!=$shortcode_atts['geo_radius'] ){
			$shortcode_atts['feed']='0';
		}
		
		if( '' != trim($shortcode_atts['dayspast']) ){
			//bug fix, when dayspast is set, date should be empty.
			$shortcode_atts['date'] = '';
		}

		if( '1'==$shortcode_atts['auto']  ){
			if( is_tax() ){
				$shortcode_atts['taxonomy'] 	= get_query_var( 'taxonomy' );
				$shortcode_atts['terms']		= get_query_var( 'term' );		
				$atts['taxonomy'] = $shortcode_atts['taxonomy'];
				$atts['terms'] = $shortcode_atts['terms'];	
			}else if( $rhc_plugin->template_frontend->is_taxonomy ){
				$shortcode_atts['taxonomy'] = $rhc_plugin->template_frontend->taxonomy;
				$shortcode_atts['terms'] = $rhc_plugin->template_frontend->term_slug;
				$atts['taxonomy'] = $shortcode_atts['taxonomy'];
				$atts['terms'] = $shortcode_atts['terms'];
			}
		}	
	
		$default_events_source = $rhc_plugin->get_option( 'rhc-api-url', '', true );
		if(''==trim($default_events_source)){
			$default_events_source = site_url('/?rhc_action=supe_get_events');
			//Compat fix: qtranslate plugin
			if( defined('QT_SUPPORTED_WP_VERSION') && function_exists('qtrans_getLanguage')){
				$default_events_source.='&lang='.qtrans_getLanguage();
			}			
		}	
		if( 1==intval( $shortcode_atts['local_tz'] ) ){
			$default_events_source.='&local_tz=1';
		}
		$shortcode_atts['ajaxurl'] = $default_events_source;	
		//---	
		//
		if( '1' == $shortcode_atts['current_post'] ){
			$shortcode_atts['post_id']  = get_the_ID();
		}
//file_put_contents( 	ABSPATH.'api.log', "post_id $post_id".print_r( $shortcode_atts,true) );			
		//---
		$shortcode_atts = apply_filters( 'supe_shortcode_atts', $shortcode_atts, $atts );
		extract($shortcode_atts);	
//'feed', 'premiere', 'loading_method', 'template', 'post_type','calendar','venue','organizer','taxonomy','terms','auto','horizon','number','showimage','words','dayspast','fcdate_format','fctime_format','calendar_url','specific_date','specific_date_end'	
		$templates = array(
			'widget_upcoming_events.php',
			'widget_upcoming_events_a.php',
			'widget_upcoming_events_a_end.php',
			'widget_upcoming_events_a_end_range.php',
			'widget_upcoming_events_a1.php',
			'widget_upcoming_events_agenda_b.php',
			'widget_upcoming_events_agenda_b2.php',
			'json',
			'php'
		);

		if( !empty( $content ) ){
			if( isset( $atts['custom_content_wrapper'] ) ){
				$content = sprintf( $atts['custom_content_wrapper'], $content );
			}else{
				$content = sprintf( '<div class="rhc-custom-template">%s</div>', $content );
			}
			
		}
		
		if( '1' == $author_current ){
			$author = 'current';
		}
		
		if( 'the_post_author' == $author ){
			$author = intval( get_the_author_meta('ID') );
			$author_name = '';
		}
		
		do_action('enqueue_frontend_only');

		global $rhc_plugin;
		if('0'!=$rhc_plugin->get_option('original_ajax_enable','',true)){
			return __('This shortcode requires that the latest version of "Ajax events query version." is enabled.','rhc');
		}
		
		$templates = apply_filters('rhc_allowed_widget_templates',$templates);
		
		if( false===strpos($template,'widget_custom_') && !in_array($template,$templates)) return '';
	
		$args = compact('page','number','parse_taxonomy','parse_taxonomymeta','order','date','date_end','dayspast','horizon','allday',
			'post_status',
			'post_type',
			'author',
			'premiere',
			'auto',
			'feed',
			'post_id',
			'current_post',
			'rdate',
			'tax_and_filter',
			'date_compare',
			'date_end_compare',
			'geo_radius',
			'geo_center',
			'taxonomy',
			'terms',
			'terms_children',
			'local_tz',
			'tz_client_server_difference',
			'tax_and_filtering',
			'template'
		);	
		
		if(!empty($taxonomy) && !empty($terms)){
			$args['taxonomy']=$taxonomy;
			$args['terms']=$terms;
		}
		if( !class_exists('rhc_supe_query') ){
			require 'class.rhc_supe_query.php';
		}
		
		$events = false;
		//handle taxonomy		
		//--- the following block is for supporting the same shortcodes inside a venue/organizer template page.
		if( property_exists( $rhc_plugin->template_frontend, 'is_taxonomy') && $rhc_plugin->template_frontend->is_taxonomy ){
			$tmp_post_id = $post_id;
			if( 'current' == $tmp_post_id ){
				$tmp_post_id = get_the_ID();
				if( $tmp_post_id > 0 ){
					switch( $rhc_plugin->template_frontend->taxonomy  ){
						case RHC_VENUE:
							$option_name = 'venue_template_page_id';
							break;
						case RHC_ORGANIZER:
							$option_name = 'organizer_template_page_id';
							break;
						default:
							$option_name = 'taxonomy_template_page_id';
					}
					$template_id = $rhc_plugin->get_option( $option_name, 0, true );
					if( empty( $template_id ) ){
						//fallback to original.
						$template_id = $rhc_plugin->get_option( 'taxonomy_template_page_id', 0, true );					
					}

					if( $template_id == $tmp_post_id ){
						//the template page is the target of this shortcode.
						if( property_exists( $rhc_plugin->template_frontend, 'term' ) ){
							$tmp_taxonomy = get_taxonomy( $rhc_plugin->template_frontend->taxonomy )		;				
							if( is_object( $tmp_taxonomy ) ){							
								$term = $rhc_plugin->template_frontend->term;
								$term->meta = array();								
								
								global $wpdb,$rhc_plugin;
								if( $rhc_plugin->wp44plus ){
									$meta_fields = $wpdb->get_col("SELECT DISTINCT(meta_key) FROM `{$wpdb->prefix}termmeta`;",0); 
								}else{
									$meta_fields = $wpdb->get_col("SELECT DISTINCT(meta_key) FROM `{$wpdb->prefix}taxonomymeta`;",0); 
								}							
								$meta_fields = is_array($meta_fields)?$meta_fields:array();
								
								if( count( $meta_fields ) > 0 ){
									foreach($meta_fields as $meta_field){
										$value = get_term_meta( $term->term_id, $meta_field, true );
										if(!empty($value)){
											$term->meta[ $meta_field ] = $value;
										}
									}									
								}
							
								
								$tmp_taxonomy->terms = array();
								$tmp_taxonomy->terms[] = $term;
								
								$tmp_e = (object)array(
									'ID'		=> $tmp_post_id,
									'post_id'	=> $tmp_post_id,
									'taxonomies'=> array( $tmp_taxonomy )
								);
								
								$events = array( $tmp_e );		
								//--- flag that a custom layout is used, template_frontend will use this to skip the built in layout.
								$rhc_plugin->template_frontend->is_custom_tax = true;					
							}
						}	
					}
				}
			}
		}
		
		//query regular events	
		if( false===$events ){
			$supe_query = new rhc_supe_query();
			$events = $supe_query->get_events( $args, $atts );					

		}

		//other output handling:
		if($template=='json'){
			return json_encode($events);
		}else if($template=='php'){
			return $events;
		}
	
		$custom_output = apply_filters('rhc_supe_custom_render', false, $events, $atts, $content);
		if(false!==$custom_output){
			return $custom_output;
		}
		
		$do_shortcode = '1'==$do_shortcode?true:false;
		
		if( empty($events) ){
			if( '1' == $hideempty ){
				return '<!-- hide_widget -->';
			}
			$output = sprintf( '<div class="rhc-supe-no-events" data-css_clear="opacity" style="opacity:0;">%s</div>' , $no_events_message );
		}else{
			if( !class_exists('rhc_supe_dom_renderer') ){
				require_once 'class.rhc_supe_dom_renderer.php';
			}
			$re = new rhc_supe_dom_renderer();
			$output = $re->render_events( $events, $atts, $content, $do_shortcode, $this->uid, $render_images );	
		}
		//$output.= $this->render_js( $atts );	

		if('1'==$the_content){
			$output = apply_filters('the_content',$output);
		}else if( $do_shortcode ){
			$output = do_shortcode($output);
		}
		
		if($separator=='eap')$holder='';

		$custom_output = apply_filters('rhc_supe_custom_output', false, $uid, $output, $atts, $shortcode_atts, compact('class','uid','page','number') );
		if(false!==$custom_output){
			return $custom_output;
		}
		
		//-- wpbakery visual composer not loading when saving shortcode in frontend editor.
		if( isset($_REQUEST['vc_editable']) && !empty($vc_js_init_script) ){

		}else{
			$vc_js_init_script = '';
		}

		$test_empty = trim( $js_init_script.$vc_js_init_script );
		if( !empty( $test_empty ) ){
			$js_init_script = sprintf("<script type='text/javascript'>try{%s;%s;}catch(e){}</script>", 
				$js_init_script,
				$vc_js_init_script
			);
		}

		$class .= ' rhc-side-'.intval($for_sidebar);
//$i=preg_match_all('/btn_tax_([a-zA-Z_\-]*)/i',$tmp,$matches)	
		$shortcode_atts['taxonomy_default'] 	= $shortcode_atts['taxonomy'];
		$shortcode_atts['terms_default'] 		= $shortcode_atts['terms'];
		
		//---- required options and class for local tz processing in client browser:
		$data_format_options = '';
		if( 1==intval( $local_tz ) ){
			$class .= ' rhc-local-tz'; //this class will be processed by js
			//-- this are needed for dates
			global $rhc_plugin;	
		
			$monthnames = $rhc_plugin->get_option( 'cal_monthnames', __('January,February,March,April,May,June,July,August,September,October,November,December','rhc'), true );
			$monthnamesshort = $rhc_plugin->get_option( 'cal_monthnamesshort', __('Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec','rhc'), true );
			$daynames = $rhc_plugin->get_option( 'cal_daynames', __('Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday','rhc'), true );
			$daynamesshort = $rhc_plugin->get_option( 'cal_daynamesshort', __('Sun,Mon,Tue,Wed,Thu,Fri,Sat','rhc'), true );
		
			$format_options = json_encode( (object)array(
				'monthNames' 		=> explode(',',$monthnames),
				'monthNamesShort' 	=> explode(',',$monthnamesshort),
				'dayNames' 			=> explode(',',$daynames),
				'dayNamesShort'		=> explode(',',$daynamesshort)		
			));		
			
			$data_format_options = sprintf("data-format_options=\"%s\"",
				esc_attr( $format_options )
			);	
		}
		//---end
		
		if('1'==$holder){
			return sprintf("<div id=\"%s_%s\" class=\"%s\" %s data-page=\"%s\" data-number=\"%s\" data-atts=\"%s\">%s<div class=\"supe-body\">%s<div class=\"supe-item-holder\">%s</div></div><div class=\"rhc-clear\"></div><div class=\"supe-footer\"></div></div>%s", 
				$prefix, 
				$uid,
				$class, 
				$data_format_options,
				$page,
				$number,
				$this->encoded_atts( $atts, $shortcode_atts ),
				$this->get_supe_head( $atts, $shortcode_atts ),
				('1'==$loading_overlay?'<div class="uew-loading"><div class="uew-loading-1"><div class="uew-loading-2 xspinner icon-xspinner-3"></div></div></div>':''),
				$output,
				$js_init_script
			);				
		}else{
			return $output.$js_init_script;
		}
	}
	
	function get_supe_head( $atts, $shortcode_atts ){
		$atts = (array)$atts;
		global $rhc_plugin;

		$out = "";
		$atts['header'] = isset( $atts['header'] ) ? $atts['header'] : '' ;
		$select = '';
		if( $i=preg_match_all('/btn_tax_([a-zA-Z_\-]*)/i',$atts['header'],$matches) ){
			$post_types = explode(',', str_replace(' ','',$atts['post_type']));
			
			foreach( $matches[1] as $taxonomy ){
				$raw_terms = $this->get_terms_for_supe_head( $taxonomy, $post_types, $atts, $shortcode_atts );

				if( is_array( $raw_terms ) && count( $raw_terms ) > 0 ){
					$select .= $this->get_taxonomy_dropdown( $taxonomy, $raw_terms );
				}							
			}
					
			$out .= sprintf("<div class=\"supe-head-holder rhcalendar\"><div class=\"supe-head rh-flat-ui\">%s</div></div>",$select);
		
			return $out;
		}		
		
		/*
		$out.= "<pre>";
		$out.= print_r($atts,true);
		$out.= "</pre>";
		*/
		
		$out .= sprintf("<div class=\"supe-head\">%s</div>",$select);
		
		return $out;
	}
	
	function get_terms_for_supe_head( $taxonomy, $post_types, $atts, $shortcode_atts ){
//TODO, get terms hierarchical	
//get_terms( array( 'taxonomy' => $taxonomy, 'child_of' => $term->term_id, 'post_type' => $post_type, 'orderby'=> 'name' ) )	
//file_put_contents( ABSPATH.'api.log', time().print_r( $shortcode_atts,true ) );
//file_put_contents( ABSPATH.'api.log', time()." ".$taxonomy );		
		if( isset( $shortcode_atts['feed'] ) && 1==intval( $shortcode_atts['feed'] ) ){
			$post_types = array( 'rhc_source' );
			$terms = $this->get_terms_by_post_type( array($taxonomy), $post_types );
//error_log( $taxonomy.' '.print_r($terms,true)."\n",3, ABSPATH.'api.log' );
			return $terms;
		}
		$hide_empty = isset( $shortcode_atts['terms_hide_empty'] ) && intval( $shortcode_atts['terms_hide_empty'] ) ? true : false ;

		if( intval($shortcode_atts['hierarchical_filter']) && !empty( $shortcode_atts['taxonomy_default'] ) && !empty( $shortcode_atts['terms_default'] ) ){
			$raw_terms = array();
			$terms_default_arr = explode(',', str_replace(' ','',$shortcode_atts['terms_default']));
			foreach( $terms_default_arr as $slug ){
				if( $term = get_term_by('slug',$slug,$taxonomy) ){
					$raw_terms[]=$term;
					
					$tmp_terms = get_terms( $taxonomy, array( 'taxonomy' => $taxonomy, 'post_type' => $post_types, 'orderby'=> 'name', 'child_of' => $term->term_id, 'hide_empty'=> $hide_empty ) );
					if( is_array( $tmp_terms ) && count( $tmp_terms ) > 0 ){
						foreach( $tmp_terms as $tmp_t ){
							$raw_terms[] = $tmp_t;
						}
					}
				}
			}

			//
		}else{
			$raw_terms = get_terms( $taxonomy, array( 'taxonomy' => $taxonomy, 'post_type' => $post_types, 'orderby'=>'name', 'hide_empty'=> $hide_empty ) );
//error_log( "LINE:".__LINE__."\n",3, ABSPATH.'api.log' );
//error_log( print_r($raw_terms,true)."\n",3, ABSPATH.'api.log' );
		}
		
		
		return $raw_terms;
	}

	static public function get_terms_by_post_type( $taxonomies, $post_types ) {

		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT t.*, COUNT(*) from $wpdb->terms AS t
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
			INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN $wpdb->posts AS p ON p.ID = r.object_id
			WHERE p.post_type IN('%s') AND tt.taxonomy IN('%s')
			GROUP BY t.term_id",
			join( "', '", $post_types ),
			join( "', '", $taxonomies )
		);

		$results = $wpdb->get_results( $query );

		return $results;

	}
	
	function get_taxonomy_dropdown( $taxonomy, $terms ){
		$tax = get_taxonomy( $taxonomy );

		$out = '<div class="tax_filter_holder fc-button fc-state-default rh-flat-ui fc-head-control tax_filter_menu_medium">';
		$out .= sprintf('<select style="display:none;" class="selectpicker not-inited" data-taxonomy="%s">',
			$taxonomy
		);
		
		$out.= sprintf('<option data-term_id="%s" value="%s">%s</option>',
			0,
			'',
			$tax->labels->name
		);		
		
		foreach( $terms as $t ){
			$out.= sprintf('<option data-term_id="%s" value="%s">%s</option>',
				$t->term_id,
				esc_attr($t->slug),
				$t->name
			);
		}
		$out.= '</select>';
		$out.= '</div>';
		return $out;
		/*
WP_Term Object
(
    [term_id] => 11509
    [name] => -Hoboken Shop B
    [slug] => hoboken-shop-b
    [term_group] => 0
    [term_taxonomy_id] => 11509
    [taxonomy] => venue
    [description] => 
    [parent] => 6
    [count] => 2
    [filter] => raw
)		
		*/
	}
	
	function encoded_atts( $atts, $shortcode_atts ){
		return esc_attr(json_encode($shortcode_atts));
	}

	function handle_rhc_dbox($atts, $content='', $code=""){
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'class'				=> ''
		), $atts));
		//.fe-extrainfo-container$box_selector .fe-extrainfo-container2
		$this->in_dbox++;
		$content = do_shortcode( $content );
		$this->in_dbox--;
		return sprintf('<div class="fe-extrainfo-container %s"><div class="fe-extrainfo-container2"><div class="fe-extrainfo-holder">%s</div></div></div>',
			$class,
			$content		
		);
	}
	
	function handle_rhc_dbox_cell($atts, $content='', $code=""){
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'label'				=> '',
			'class'				=> ''
		), $atts));
		
		if( empty( $label ) ){
			$class.='fe-is-empty-label-0';
		}else{
			$class.='fe-is-empty-label-0';
		}
		
		$content = do_shortcode( $content );
		
		
		return sprintf('<div class="rhc-info-cell %s"><label class="fe-extrainfo-label">%s</label><span>%s</span></div>',
			$class,
			$label,
			$content		
		);
	}
		     
	function handle_rhc_date( $atts, $content='', $code=""){
		global $rhc_plugin;
		$atts = (array)$atts;
		extract(shortcode_atts(array(
			'post_id' 			=> '',
			'date_format'		=> 'MMMM d, yyyy',
			'time_format'		=> 'h:mm tt',
			'allday_hide'		=> '',
			'label'				=> '',
			'label_start'		=> __('Start','rhc'),
			'label_end'			=> __('End','rhc'),
			'class'				=> 'rhc-sc-date'
		), $atts));
		
		$post_id = empty($post_id) ? get_the_ID() : intval( $post_id );
		$is_allday = '1' == get_post_meta( $post_id, 'fc_allday', true ) ? true : false ;
		
		if( intval( $allday_hide ) && $is_allday ){
			return '';
		}
		
		if( !empty( $label ) ){
			if( in_array( $code, array('rhc_start','rhc_start_date','rhc_start_time')) ){
				$label_start = $label;
			}else{
				$label_end = $label;
			}
		}

		$fc_start_datetime 	= get_post_meta($post_id,'fc_start_datetime',true);
		$fc_end_datetime 	= get_post_meta($post_id,'fc_end_datetime',true);
		if( empty( $fc_start_datetime ) || empty( $fc_end_datetime ) ){
			return '';
		}
		
		$class .= empty( $class ) ? 'sc-'.$code : ' sc-'.$code;
		
		$start 	= strtotime( $fc_start_datetime );
		$end 	= strtotime( $fc_end_datetime );
		if( date('Ymd', $start) == date('Ymd', $end ) ){
			$class .= ' same-day';
		}else{
			$class .= ' not-same-day';
		}
		
		if( date('Ym', $start) == date('Ym', $end ) ){
			$class .= ' same-month';
		}else{
			$class .= ' not-same-month';
		}
		
		$content = $this->handle_event_date_get_content( $atts, $content, $code );
//TODO: LOad from settings		
		$date_format = empty( $date_format ) ? $rhc_plugin->get_option( 'cal_rhc_sc_date_format', 'MMMM d, yyyy', true ) : $date_format ;
		$time_format = empty( $time_format ) ? $rhc_plugin->get_option( 'cal_rhc_sc_time_format', 'h:mm tt', true ) : $time_format ;
		
		if( $is_allday ) {
			$class .= ' fc-is-allday';
		}
		
		return sprintf( $content, 
			$class,												// holder class %1$s
			$label_start,										// start label	%2$s
			fc_get_repeat_start_date( $post_id, $date_format ), // start date	%3$s
			fc_get_repeat_start_date( $post_id, $time_format ), // start time	%4$s
			$label_end,											// end label	%5$s				
			fc_get_repeat_end_date( $post_id, $date_format ),	// end date		%6$s
			fc_get_repeat_end_date( $post_id, $time_format )	// end time		%7$s
		);	
	}	

	function handle_event_date_get_content( $atts, $content='', $code="" ){
		$atts = (array)$atts;
		if( empty( $content ) ){
			if( $this->in_dbox ){
				switch( $code ){
					case 'rhc_start':
						$content = '<div class="rhc-info-cell %1$s"><label class="fe-extrainfo-label">%2$s</label><span class="fe-extrainfo-value"><span class="rhc-sc-date">%3$s</span><span class="rhc-sc-time">%4$s</span></span></div>';
						break;
					case 'rhc_start_date':
						$content = '<div class="rhc-info-cell %1$s"><label class="fe-extrainfo-label">%2$s</label><span class="fe-extrainfo-value"><span class="rhc-sc-date">%3$s</span></span></div>';
						break;
					case 'rhc_start_time':
						$content = '<div class="rhc-info-cell %1$s"><label class="fe-extrainfo-label">%2$s</label><span class="fe-extrainfo-value"><span class="rhc-sc-time">%4$s</span></span></div>';
						break;
					case 'rhc_end':
						$content = '<div class="rhc-info-cell %1$s"><label class="fe-extrainfo-label">%5$s</label><span class="fe-extrainfo-value"><span class="rhc-sc-date">%6$s</span><span class="rhc-sc-time">%7$s</span></span></div>';
						break;
					case 'rhc_end_date':
						$content = '<div class="rhc-info-cell %1$s"><label class="fe-extrainfo-label">%5$s</label><span class="fe-extrainfo-value"><span class="rhc-sc-date">%6$s</span></span></div>';					
						break;
					case 'rhc_end_time':
						$content = '<div class="rhc-info-cell %1$s"><label class="fe-extrainfo-label">%5$s</label><span class="fe-extrainfo-value"><span class="rhc-sc-time">%7$s</span></span></div>';
						break;
				}				
			}else{
				switch( $code ){
					case 'rhc_start':
						$content = '<span class="%1$s"><strong>%2$s</strong><span class="rhc-sc-date">%3$s</span><span class="rhc-sc-time">%4$s</span></span>';
						break;
					case 'rhc_start_date':
						$content = '<span class="%1$s"><strong>%2$s</strong><span>%3$s</span></span>';
						break;
					case 'rhc_start_time':
						$content = '<span class="%1$s rhc-sc-time"><strong>%2$s</strong><span>%4$s</span></span>';
						break;
					case 'rhc_end':
						$content = '<span class="%1$s"><strong>%5$s</strong><span class="rhc-sc-date">%6$s</span><span class="rhc-sc-time">%7$s</span></span>';
						break;
					case 'rhc_end_date':
						$content = '<span class="%1$s"><strong>%5$s</strong><span>%6$s</span></span>';
						break;
					case 'rhc_end_time':
						$content = '<span class="%1$s rhc-sc-time"><strong>%5$s</strong><span>%7$s</span></span>';
						break;
				}
			}

		}else{
			$content = html_entity_decode( $content );
		}
		return $content;
	}	
		
}

/* wrapper map
rhc_title				--- ---------------	---
rhc_description			--- ---------------	---
rhc_label				--- ---------------	---
							
rhc_tax_loop			---
rhc_venue_loop			--- rhc_tax_loop 	---
rhc_organizer_loop		---
													rhc_event_details		--- rhc_static_upcoming_events

rhc_term				---
rhc_venue_term			--- rhc_term		---
rhc_organizer_term		---
rhc_calendar_term		---


rhc_venue_meta			--- 
rhc_organizer_meta 		---
rhc_venue_image			--- rhc_term_meta	---
rhc_venue_website		---
rhc_organizer_image		---
rhc_organizer_website 	---

handle_rhc_gmap			--- ---------------	--- ---------------------------	---
*/
?>