<?php


class rhc_fusion_builder {
	function __construct(){
		add_action( 'fusion_builder_before_init', array( &$this, 'fusion_builder_before_init' ), 3 );
		
		add_filter( 'pop_calendarizeit_options_for_fb_params', array( &$this, 'pop_calendarizeit_options_for_fb_params' ), 10, 1);
	}
	
	function fusion_builder_before_init(){

		fusion_builder_map(
			array(
				'name'              => esc_attr__( 'RHC - Calendarize it!', 'rhc' ),
				'shortcode'         => 'calendarizeit',
				'multi'				=> false,
				'params'            => $this->get_fb_map_params_for_calendarizeit()
			)
		);
		
		fusion_builder_map(
			array(
				'name'              => esc_attr__( 'RHC - Upcoming Events', 'rhc' ),
				'shortcode'         => 'rhc_static_upcoming_events',
				'multi'				=> false,
				'params'            => $this->get_fb_map_params_for_supe()
			)
		);		
	
	}
	
	function pop_calendarizeit_options_for_fb_params( $t ){
		$more_options = $this->get_filter_tab_options_for_vc_params();
		foreach( $t as $i => $tab ){
			foreach( $tab->options as $j => $o ){
				if( intval( @$o->vc_tab ) && 'vc_tab_labels' == @$o->id  ){
					array_splice( $t[$i]->options, $j, 0, $more_options );
					break 2;
				}
			}
		}
		return $t;
	}
	//shortcode calendarize-it filter tab
	function get_filter_tab_options_for_vc_params(){
//error_log( date('Y-m-d H:i:s')."<-- vc get_filter_tab_options_for_vc_params\n", 3, ABSPATH.'vc.log' );
		//in pop syntax
		global $rhc_plugin;
		$options = array();
	 		
		$post_types = $rhc_plugin->get_option('post_types',array());
		$post_types = is_array($post_types) ? $post_types:array();
		array_unshift( $post_types, RHC_EVENTS );
		$post_types = apply_filters('rhc_calendar_metabox_post_types',$post_types);
		
		$options[] = (object)array(
				'id'			=> 'vc_tab_filter',
				'type' 			=> 'vc_tab', 
				'label'			=> __('Filter','rhc'),
				'vc_tab'		=> true //flat the start of a tab in vc.
			);
		
		$j = 0;	

		foreach($post_types as $post_type){

			$rhc_post_type_labels = array(
				RHC_EVENTS => __('Events','rhc')
			);
		
			$rhc_post_type_labels = apply_filters('rhc_post_type_labels',$rhc_post_type_labels);
			
			if( $pt = get_post_type_object( $post_type ) ){
			
			}else{
				//for some reason the events post type is not registered at this stage.
				$pt = (object)array(
					'name' 		=> $post_type,
					'labels'	=> (object)array(
						'name' => isset( $rhc_post_type_labels[ $post_type ] ) ? $rhc_post_type_labels[ $post_type ] : str_replace('_',' ', ucfirst( $post_type ) )
					)
				);
			}
				
			$tmp=(object)array(
				'id'	=> 'post_type_'.$pt->name,
				'name'	=> 'cal_post_type[]',
				'type'	=> 'checkbox',
				'option_value'=>$post_type,
				'default'	=> '',
				'label'	=> $pt->labels->name,
				'el_properties' => array(),
				'save_option'=>true,
				'load_option'=>true,
				'vc_label' => __('Post types')
			);
			if($j==0){
				$tmp->description = __("Choose post types to include in the calendar.",'rhc');
			}
			$options[]=$tmp;
			$j++;	
		}
			
		$options[] = (object)array(
				'id'			=> 'cal_author',
				'name'			=> 'author',
				'type' 			=> 'checkbox',
				'label'			=> __('Display events authored by the user that is logged in.','rhc'),
				'vc_label'		=> __('Logged user events','rhc'),
				'option_value'	=> 'current_user',
				'description'	=> __('Check this option to display events from the logged in user.  Observe that you need to go to Calendarize It! (menu) -> Options (submenu) -> Events cache (tab) and enable "Cache by user" (yes).', 'rhc')
			);
			
		$options[] = (object)array(
				'id'			=> 'cal_author_name',
				'type' 			=> 'text',
				'label'			=> __('Author','rhc'),
				'description'	=> __('Write an author user_login to display events from that author only', 'rhc')
			);
			
		$options[] = (object)array(
				'id'			=> 'cal_gotodate',
				'type' 			=> 'text',
				'label'			=> __('Go to date','rhc'),
				'description'	=> __('Format: Y-m-d, specify a starting date.  Example 2016-05-01', 'rhc')
			);

		$options[] = (object)array(
				'id'		=> 'cal_auto',
				'label'		=> __('Related events','rhc'),
				'type'		=> 'onoff',
				'default'	=> '0',
				'description' => __('Choose yes to only show events with the same taxonomy and term as the loaded page.  Used on venue template content.','rhc')
			);
		
		$default_taxonomies = array(
			''				=> __('--none--','rhc'),
			RHC_CALENDAR 	=> __('Calendar','rhc'),
			RHC_ORGANIZER	=> __('Organizer','rhc'),
			RHC_VENUE		=> __('Venues','rhc')
		);
		
		$taxonomies = apply_filters('rhc-taxonomies',$default_taxonomies);
		
		
		foreach( $post_types as $post_type ){
			$tmp = get_object_taxonomies(array('post_type'=>$post_type),'objects');
			if( is_array($tmp) && count($tmp) > 0 ){
				foreach( $tmp as $taxonomy => $tax ){
					$taxonomies[$taxonomy] = $tax->labels->name;
				}
			}
		}			

		$options[] = (object)array(
				'id'			=> 'cal_taxonomy',
				'type' 			=> 'select',
				'label'			=> __('Taxonomy','rhc'),
				'options'		=> $taxonomies,
				'description'	=> __('Choose a taxonomy and terms to filter events.', 'rhc')
			);
		//----- terms ------
		if( 'dropdown' == $rhc_plugin->get_option('vc_term_input', 'text', true ) ){
			$taxonomy_ids = array_filter(array_keys( $taxonomies ));

			if( is_array( $taxonomy_ids ) && count( $taxonomy_ids ) > 0 ){
				$terms = get_terms( $taxonomy_ids );			
				if( !empty($terms) ){					
					foreach( $taxonomy_ids as $tax_id ){
						$field_options = array();
						foreach( $terms as $term ){
							if( $term->taxonomy != $tax_id || !is_object( $term ) ) continue;
						
							$options[] = (object)array(
								'id'			=> 'cal_terms_'.$tax_id.'-'.$term->term_id,
								'name'			=> 'cal_terms[]',
								'type' 			=> 'checkbox',
								'label'			=> $term->name,
								'vc_label'		=> __('Terms','rhc'),
								'option_value'	=> $term->slug/*,
								"vc_dependency" 	=>array("element" => "taxonomy","value" => array($tax_id))*/
							);							
						}	
					}
				}
				/*
				$options[] = (object)array(
						'id'			=> 'cal_terms',
						'type' 			=> 'select',
						'label'			=> __('Local/External sources (feeds)','rhc'),
						'options'		=> apply_filters('rhc_views', array(
							''			=> __('Both local and external sources','rhc'),
							'0'			=> __('Only local','rhc'),
							'1'			=> __('Only external sources','rhc')
						))
					);
				*/	
			}
		}else{
			$options[] = (object)array(
				'id'			=> 'cal_terms',
				'type' 			=> 'text',
				'label'			=> __('Terms','rhc'),
				'description'	=> __('Comma separated term slug. (Not label)', 'rhc')
			);	
		}
		//-----------------------
		
		$options[] = (object)array(
				'id'			=> 'cal_feed',
				'type' 			=> 'select',
				'label'			=> __('Local/External sources (feeds)','rhc'),
				'options'		=> apply_filters('rhc_views', array(
					''			=> __('Both local and external sources','rhc'),
					'0'			=> __('Only local','rhc'),
					'1'			=> __('Only external sources','rhc')
				))
			);
			
		return $options;
	}	
		
	function get_fb_map_params_for_calendarizeit(){
		$t = array();
		//options in RightHere options syntax.
		include 'options.calendarize_shortcode.php';
		$t = apply_filters( 'pop_calendarizeit_options_for_fb_params', $t );

/* this is only to generate a quick reference of rhc options types to conver to vc.	*/
/*
		$pop_types = array();
		foreach( $t as $i => $tab ){
			foreach( $tab->options as $j => $option ) {	
				if( in_array( $option->type, $pop_types ) ) continue;
				$pop_types[]=$option->type;
			}	
		}
*/		
/*
echo "<pre>";
print_r($t);
print_r($pop_types);
echo "</Pre>";	
*/
		$this->set_pop_conditional_options( $t, $i );	

		return $this->convert_rhc_options_to_fb_params( $t );
	}	
	
	function get_fb_map_params_for_supe(){
		global $rhc_plugin;	
		require_once RHC_PATH.'includes/class.rh_templates.php';
		$t = new rh_templates( array('template_directory'=>$rhc_plugin->get_template_path()) );
		$templates = $t->get_template_files('widget_upcoming_events');
		$templates = is_array($templates)&&count($templates)>0?$templates:array('widget_upcoming_events.php');		
		$templates = apply_filters('rhc_uew_templates', $templates);	
		if( defined('RHCAEW_PATH') ){
			//note: it was easier to hardcode this.
			$templates['widget_custom_accordion.php'] 		= __('Accordion - Default','rhc');
			$templates['widget_custom_image_expands.php'] 	= __('Accordion - Image expands','rhc');
			$templates['widget_custom_date_tilts.php'] 		= __('Accordion - Date tilts','rhc');
		}
		//---
		$t = array();
		$i = 0;
		
		$t[$i]->options[]=(object)array(
				'id'			=> 'vc_tab_general',
				'type' 			=> 'vc_tab', 
				'label'			=> __('General','rhc'),
				'vc_tab'		=> true //flat the start of a tab in vc.
			);		
		
		$t[$i]->options[]=(object)array(
				'id'			=> 'cal_template',
				'type' 			=> 'select',
				'label'			=> __('Template','rhc'),
				'options'		=> $templates
			);	

		$t[$i]->options[]=(object)array(
				'id'		=> 'cal_nav',
				'label'		=> __('Navigation','rhc'),
				'type'		=> 'onoff',
				'default'	=> '0',
				'description' => __('Display navigation controls.','rhc'),
				'el_properties'	=> array(),
				'save_option'=>true,
				'load_option'=>true
			);

		$t[$i]->options[]=(object)array(
				'id'	=> 'cal_number',
				'type'	=> 'range',
				'label' => '',
				'vc_label'	=> __('Number of events','rhc'),
				'vc_admin_label' => true,
				'min'	=> 0,
				'max'	=> 500,
				'step'	=> 1,
				'vc_default'=> 20
			);	

		$t[$i]->options[]=(object)array(
				'id'			=> 'cal_order',
				'type' 			=> 'select',
				'label'			=> __('Order','rhc'),
				'options'		=> array(
					'ASC'	=> __('Ascending','rhc'),
					'DESC'	=> __('Descending', 'rhc')
				)
			);	
			
		$t[$i]->options[]=(object)array(
				'id'			=> 'cal_horizon',
				'type' 			=> 'select',
				'label'			=> __('Remove event by','rhc'),
				'options'		=> array(
					'day'	=> __('Day', 'rhc'),
					'hour'	=> __('Hour','rhc'),
					'end'	=> __('By event end', 'rhc')
				)
			);			

		$t[$i]->options[] = (object)array(
				'id'			=> 'cal_no_events_message',
				'type' 			=> 'text',
				'label'			=> __('No events message','rhc'),
				'description'	=> __('Specify a text to show if there are no more events.', 'rhc')
			);	

		$t[$i]->options[] = (object)array(
				'id'			=> 'vc_tab_filter',
				'type' 			=> 'vc_tab', 
				'label'			=> __('Filter','rhc'),
				'vc_tab'		=> true //flat the start of a tab in vc.
			);		

		$t[$i]->options[] = (object)array(
				'id'			=> 'cal_current_post',
				'name'			=> 'current_post',
				'type' 			=> 'checkbox',
				'label'			=> __('Current event recurring instances','rhc'),
				'vc_label'		=> __('Display recurring events.','rhc'),
				'option_value'	=> '1',
				'description'	=> __('Check this option to display recurring instances of the current post.  Expected inside a loop (get_the_ID).', 'rhc')
			);
			
		$t[$i]->options[] = (object)array(
				'id'			=> 'cal_post_id',
				'type' 			=> 'text',
				'label'			=> __('Post ID','rhc'),
				'description'	=> __('Specify a post ID if you want to show a list of recurring instances for that particular event.  This is overwritten by the previous option.', 'rhc')
			);				
		//-----------	
		$post_types = $rhc_plugin->get_option('post_types',array());
		$post_types = is_array($post_types) ? $post_types:array();
		array_unshift( $post_types, RHC_EVENTS );
		$post_types = apply_filters('rhc_calendar_metabox_post_types',$post_types);			
			
		$j = 0;	
		foreach($post_types as $post_type){

			$rhc_post_type_labels = array(
				RHC_EVENTS => __('Events','rhc')
			);
		
			$rhc_post_type_labels = apply_filters('rhc_post_type_labels',$rhc_post_type_labels);
			
			if( $pt = get_post_type_object( $post_type ) ){
			
			}else{
				//for some reason the events post type is not registered at this stage.
				$pt = (object)array(
					'name' 		=> $post_type,
					'labels'	=> (object)array(
						'name' => isset( $rhc_post_type_labels[ $post_type ] ) ? $rhc_post_type_labels[ $post_type ] : str_replace('_',' ', ucfirst( $post_type ) )
					)
				);
			}
				
			$tmp=(object)array(
				'id'	=> 'cal_post_type_'.$pt->name,
				'name'	=> 'cal_post_type[]',
				'type'	=> 'checkbox',
				'option_value'=>$post_type,
				'default'	=> '',
				'label'	=> $pt->labels->name,
				'el_properties' => array(),
				'save_option'=>true,
				'load_option'=>true,
				'vc_label' => __('Post types')
			);
			if($j==0){
				$tmp->description = __("Choose post types to include in the list.",'rhc');
			}
			$t[$i]->options[]=$tmp;
			$j++;	
		}			

		$t[$i]->options[] = (object)array(
				'id'			=> 'cal_author_current',
				'name'			=> 'author_current',
				'type' 			=> 'checkbox',
				'label'			=> __('Display events authored by the user that is logged in.','rhc'),
				'vc_label'		=> __('Logged user events','rhc'),
				'option_value'	=> '1',
				'description'	=> __('Check this option to display events from the logged in user.', 'rhc')
			);
			
		$t[$i]->options[] = (object)array(
				'id'			=> 'cal_author',
				'type' 			=> 'text',
				'label'			=> __('Author','rhc'),
				'description'	=> __('Write an author user_login to display events from that author only', 'rhc')
			);			

		$t[$i]->options[]=(object)array(
				'id'		=> 'cal_auto',
				'label'		=> __('Related events','rhc'),
				'type'		=> 'onoff',
				'default'	=> '0',
				'description' => __('Choose yes to only show events with the same taxonomy and term as the loaded page.  Used on venue template content.','rhc')
			);

		$default_taxonomies = array(
			''				=> __('--none--','rhc'),
			RHC_CALENDAR 	=> __('Calendar','rhc'),
			RHC_ORGANIZER	=> __('Organizer','rhc'),
			RHC_VENUE		=> __('Venues','rhc')
		);
		
		$taxonomies = apply_filters('rhc-taxonomies',$default_taxonomies);
		
		
		foreach( $post_types as $post_type ){
			$tmp = get_object_taxonomies(array('post_type'=>$post_type),'objects');
			if( is_array($tmp) && count($tmp) > 0 ){
				foreach( $tmp as $taxonomy => $tax ){
					$taxonomies[$taxonomy] = $tax->labels->name;
				}
			}
		}			

		$t[$i]->options[] = (object)array(
				'id'			=> 'cal_taxonomy',
				'type' 			=> 'select',
				'label'			=> __('Taxonomy','rhc'),
				'options'		=> $taxonomies,
				'description'	=> __('Choose a taxonomy and terms to filter events.', 'rhc')
			);
		
		if( 'dropdown' == $rhc_plugin->get_option('vc_term_input', 'text', true ) ){
			
		}else{
			$t[$i]->options[] = (object)array(
					'id'			=> 'cal_terms',
					'type' 			=> 'text',
					'label'			=> __('Terms','rhc'),
					'description'	=> __('Comma separated term slug. (Not label)', 'rhc')
				);
		}



		$t[$i]->options[]=(object)array(
				'id'			=> 'vc_tab_format',
				'type' 			=> 'vc_tab', 
				'label'			=> __('Format','rhc'),
				'vc_tab'		=> true //flat the start of a tab in vc.
			);

		$t[$i]->options[]=(object)array(
				'id'			=> 'cal_date_format',
				'type' 			=> 'text',
				'label'			=> __('Date format','rhc')
			);	
		$t[$i]->options[]=(object)array(
				'id'			=> 'cal_time_format',
				'type' 			=> 'text',
				'label'			=> __('Time format','rhc')
			);	

		$t[$i]->options[]=(object)array(
				'id'		=> 'cal_premiere',
				'label'		=> __('Premiere','rhc'),
				'type'		=> 'onoff',
				'default'	=> '0',
				'description' => __('Choose yes to only show the first event in a recurring set.','rhc'),
				'el_properties'	=> array(),
				'save_option'=>true,
				'load_option'=>true
			);

		$t[$i]->options[]=(object)array(
				'id'			=> 'vc_tab_other',
				'type' 			=> 'vc_tab', 
				'label'			=> __('Other','rhc'),
				'vc_tab'		=> true //flat the start of a tab in vc.
			);			
			
		$this->set_pop_conditional_options( $t, $i );		
			
		return $this->convert_rhc_options_to_fb_params( $t );
	}
	
	function convert_rhc_options_to_fb_params( $t ){
		$group = '';
		$unhandled_types = array();
		$params = array();

	//---TODO: merge all checkboxes from the same name into a single param item.		
	//--- the value should contain an array where the label is the index and the value is the array value.
/*

	$visibility_options = array(
		esc_attr__( 'Small Screen', 'fusion-builder' )  => 'small-visibility',
		esc_attr__( 'Medium Screen', 'fusion-builder' )  => 'medium-visibility',
		esc_attr__( 'Large Screen', 'fusion-builder' ) => 'large-visibility',
		);

*/
/*		
echo "<pre>";
print_r( $t );
echo "</pre>";
die();
*/

		$done = array();
		
		foreach( $t as $i => $tab ){
			foreach( $tab->options as $j => $option ) {	
			
				if( property_exists( $option, 'vc_tab' ) && $option->vc_tab ){
					$group = $option->label;
				}
			
				if( $option->type == 'checkbox' ){
					$id = str_replace('[]', '', $option->name );
					if( in_array( $id, $done ) ){
						continue;
					}else{
						$done[]=$id;
					}
					
					$this->handle_checkbox( $params, $id, $option, $t, $group );

					continue;
				}
	
				try {
					if( !empty($option->vc_skip) && $option->vc_skip ) continue;

					$params[] = $this->get_vc_param( $option, $tab->options, $group, null, $i );
				}catch( Exception $e ){
					$message = $option->type." ".$e->getMessage();
					if( !array_key_exists( $message, $unhandled_types ) ){
						$unhandled_types[ $message] = 1;
					}else{
						$unhandled_types[ $message ]++;
					}
//file_put_contents( ABSPATH.'fb.log', print_r( $unhandled_types, true ) );						
				}
			}	
		}		
/*
echo "<pre>";
print_r( $params );
echo "</pre>";
die();
*/		
		return $params;
	}

	function set_pop_conditional_options( &$t, $i ){
		$t[$i]->options[]=(object)array(
				'id'			=> 'vc_tab_condition',
				'type' 			=> 'vc_tab', 
				'label'			=> __('Conditions','rhc'),
				'vc_tab'		=> true //flat the start of a tab in vc.
			);		
	
		$t[$i]->options[]=(object)array(
				'id'	=> 'cal_capability',
				'type'	=> 'text',
				'label' => __('Permission (capability)','rhc'),
				'description' => __( 'If used, the shortcode will only display if the user is logged in and have the specific capability.', 'rhc')
			);	
		$conditional_tags = apply_filters( 'postinfo_allowed_conditional_tags', array( 
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
				));
		$j = 0;	
		foreach($conditional_tags as $is_condition){				
			$tmp=(object)array(
				'id'			=> 'cal_conditional_tag_'.$is_condition,
				'name'			=> 'cal_conditional_tag[]',
				'type'			=> 'checkbox',
				'option_value'	=>$is_condition,
				'default'		=> '',
				'label'			=> $is_condition,
				'vc_label' 		=> __('Conditional Render','rhc')
			);
			if($j==0){
				$tmp->description = __("Check the conditions to test for displaying the shortcode.  Leave empty to display everywhere, included feeds and trackbacks.",'rhc');
			}
			$t[$i]->options[]=$tmp;
			$j++;	
		}					
	}

	
	function handle_checkbox( &$params, $id, $o, $t, $group='' ){
		$value = array(
			$o->label => $o->option_value
		);
		
		$default = array();
		if( !empty( $o->default ) ){
			$default[] = $o->default;
		}
		
		foreach( $t as $i => $tab ){
			foreach( $tab->options as $j => $option ) {	
				$current_id = str_replace('[]', '', $option->name );
				if( $current_id == $id ){
					$value[ $option->label ] = $option->option_value;
					$default[] = $option->default;
				}
			}
		}	

		$param = array(
			'type'        => 'checkbox_button_set',
			'heading'     => $o->vc_label,
			'param_name'  => $id,
			'value'       => $value,
			'default'     => implode(', ',$default),
			'description' => '',
		);	
		
		$this->set_group( $param, $group );
		
		$this->set_vc_field( $param, 'description', $this->get_vc_description( $o ) );
/*
echo $id;
echo "<pre>";
print_r( $o );
echo "</pre>";
die();	
*/
		$params[] = $param;
	}
	
	function get_vc_param( $rhc_option, $rhc_options, $group='', $tab, $i ){
	
		$method = 'get_vc_param_from_rhc_' . $rhc_option->type;
		if( method_exists( $this, $method ) ){
			//render using vc renderer
			$param = $this->$method( $rhc_option, $rhc_options );
			$this->set_admin_label( $param, $rhc_option );
			$this->set_group( $param, $group );
			
			return $param;
		} 
			
		throw new Exception( 'RHC Option to VC Param method not found: ' . $method );

	}	
	
	function get_vc_param_from_rhc_text( $o ){
	
		$param = array(
					'type'        => 'textfield',
					'heading'     => $o->label,
					'description' => '',
					'param_name'  => str_replace( 'cal_', '', $o->id ),
					'value'       => '',
					'placeholder' => true,
				);
	
		//if value is set with an empty value, the shortcode always render with the attribute even if empty.	
		$this->set_vc_field( $param, 'description', $this->get_vc_description( $o ) );	
		$this->set_vc_field( $param, 'value', (property_exists( $o, 'default' ) ? $o->default : '') );	
			
		return $param;
	}

	function get_vc_param_from_rhc_textarea( $o ){
		$param = array(
					'type'        => 'textarea',
					'heading'     => $o->label,
					'description' => '',
					'param_name'  => str_replace( 'cal_', '', $o->id ),
					'value'       => '',
					'placeholder' => true,
				);
	
		//if value is set with an empty value, the shortcode always render with the attribute even if empty.	
		$this->set_vc_field( $param, 'description', $this->get_vc_description( $o ) );	
		$this->set_vc_field( $param, 'value', (property_exists( $o, 'default' ) ? $o->default : '') );	
			
		return $param;	
	}

	function get_vc_param_from_rhc_select( $o ){
		$param = array(
				"type" 			=> "select",
				"class" 		=> "",
				"heading" 		=> $o->label,
				"param_name" 	=> str_replace( 'cal_', '', $o->id ),
				"value" 		=> $this->rhc_to_vc_dropdown_options( $o->options )
			);
			
		$this->set_vc_field( $param, 'description', $this->get_vc_description( $o ) );	
		$this->set_vc_field( $param, 'std', (property_exists( $o, 'default' ) ? $o->default : '') );	
		
		return $param;
	}

	function get_vc_param_from_rhc_yesno( $o ){
		$param = array(
				"type" 			=> "radio_button_set",
				"class" 		=> "",
				"heading" 		=> $o->label,
				"param_name" 	=> str_replace( 'cal_', '', $o->id ),
				"value" 		=> $this->rhc_to_vc_dropdown_options( array(
					'1'=>__('Yes','rhc'),
					'0'=>__('No','rhc')
				) )
			);
			
		$this->set_vc_field( $param, 'description', $this->get_vc_description( $o ) );	
		$this->set_vc_field( $param, 'std', (property_exists( $o, 'default' ) ? $o->default : '') );	
		
		return $param;	
	}

	function get_vc_param_from_rhc_onoff( $o ){
		$param = array(
				"type" 			=> "radio_button_set",
				"class" 		=> "",
				"heading" 		=> $o->label,
				"param_name" 	=> str_replace( 'cal_', '', $o->id ),
				"value" 		=> $this->rhc_to_vc_dropdown_options( array(
					'1'=>__('On','rhc'),
					'0'=>__('Off','rhc')
				) )
			);
			
		$this->set_vc_field( $param, 'description', $this->get_vc_description( $o ) );	
		$this->set_vc_field( $param, 'std', (property_exists( $o, 'default' ) ? $o->default : '') );	
		
		return $param;	
	}

	function notused_get_vc_param_from_rhc_checkbox( $o ){
		$param = array(
				"type" 			=> "checkbox_button_set",
				"heading" 		=> $o->label,
				"param_name" 	=> str_replace( 'cal_', '', $o->id ),
				"value" 		=> $o->option_value
			);
			
		$this->set_vc_field( $param, 'description', $this->get_vc_description( $o ) );	
		
		return $param;	
	}

	function get_vc_param_from_rhc_range( $o ){
		$param = array(
					'type'        => 'range',
					'heading'     => $o->label,
					'description' => '',
					'param_name'  => str_replace( 'cal_', '', $o->id ),
					'value'       => '',
					'min'         => (string)$o->min,
					'max'         => (string)$o->max,
					'step'        => (string)$o->step				
				);

		//if value is set with an empty value, the shortcode always render with the attribute even if empty.	
		$this->set_vc_field( $param, 'description', $this->get_vc_description( $o ) );	
		$this->set_vc_field( $param, 'value', (property_exists( $o, 'default' ) ? $o->default : '') );	
			
		return $param;	
	}


	function rhc_to_vc_dropdown_options( $options ){
		$vc_options = array();
		foreach( $options as $value => $label ){
			$value = (string)$value;
			$vc_options[$label] = $value;
		}
		return $vc_options;
	}

	function set_group( &$param, $group ){
		if( !empty( $group ) ){
			$param['group'] = $group;
		}
	}
	
	function set_admin_label( &$param, $pop_option ){
		//is this implemented in FB?
		if( property_exists( $pop_option, 'vc_admin_label') ){
			$param['admin_label'] = $pop_option->vc_admin_label;
		}
	}
	
	function get_vc_description( $o ){
		return property_exists( $o, 'vc_description' ) ? $o->vc_description : @$o->description ;
	}
		
	function set_vc_field( &$param, $field, $value ){
		if( ''!=trim($value) ){
			$param[$field]=$value;
		}
	}	
}