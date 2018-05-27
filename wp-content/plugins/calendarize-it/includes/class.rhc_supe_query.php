<?php

class rhc_supe_query {
	function __construct(){
	
	}
	
	public static function handle_and_filtering( &$r ){
//file_put_contents( ABSPATH.'api.log', time()."rhc_supe_query::get_events\n".print_r( $r,true) );
//file_put_contents( ABSPATH.'api.log', '' );
		if( isset( $r['tax_and_filtering'] ) && 1 == intval( $r['tax_and_filtering'] ) ){
			if( isset( $r['taxonomy'] ) && isset( $r['terms'] ) && !empty( $r['taxonomy'] ) && !empty( $r['terms'] ) ){
				$terms = array();
				$terms_arr = explode( ',', $r['terms'] );
				foreach( $terms_arr as $term ){
					$term = trim( $term );
					if( empty( $term ) ) continue;
					$terms[] = $term;		
				}

				if( count( $terms ) > 0 ){
					$tmp = array();
					$taxonomy_arr = explode( ',', $r['taxonomy'] );
					foreach( $taxonomy_arr as $taxonomy ){
						$taxonomy = trim( $taxonomy );
						if( empty( $taxonomy ) ) continue;
						$tmp[] = sprintf('%s:%s',
							$taxonomy,
							implode( ',', $terms )
						);
					}	
					
					if( isset( $r['tax_and_filter'] ) && !empty( $r['tax_and_filter'] ) ){
						$r['tax_and_filter'] .= ';' . implode( ';', $tmp ) ;
					}else{
						$r['tax_and_filter'] = implode( ';', $tmp ) ;
					}					
				}
			}
		}
	}
	
	public static function get_events( $args=array(), $atts=array() ){
		global $wpdb;
//file_put_contents( ABSPATH.'api.log', time()."rhc_supe_query::get_events\n".print_r( $args,true) );	
//file_put_contents( ABSPATH.'api.log', time()."rhc_supe_query::get_events\n" );	
		$defaults = array(
			'page' 		=> '0',
			'number'	=> '5',
			'order' 	=> 'ASC',
			'date'		=> 'NOW',
			'date_end' 	=> '',
			'date_compare'	=> '',
			'date_end_compare' => '',
			'dayspast'	=> '',
			'horizon'	=> 'hour',//hour,day,end
			'allday'	=> '',
			'post_status' 	=> 'publish',
			'post_type'		=> '',
			'author'	=> '',
			'parse_postmeta' => '',//comma separated fields to include in the event ovent as a meta array().
			'parse_taxonomy' => '0',
			'parse_taxonomymeta' => '0',
			'premiere'		=> '0',
			'auto'			=> '0',
			'feed'			=> '',
			'post_id'		=> '',
			'current_post'	=> '',
			'rdate'			=> '',
			'tax_and_filter'=> '',
			'taxonomy'		=> '',
			'terms'			=> '',
			'terms_children'=> '',
			'local_tz'		=> '',
			'tz_client_server_difference' => '0',
			'tax_and_filtering' => '0'
		);
		
		//rhc_supe_query::handle_geo_radius( $args );
		
//TODO:take into consideration the browser tz.
		$r = wp_parse_args( $args, $defaults );
//file_put_contents( ABSPATH.'api.log', time()."rhc_supe_query::get_events\n".print_r( $r,true) );	
		rhc_supe_query::handle_and_filtering( $r );
//error_log( "\n".print_r($r,true)."\n", 3, ABSPATH.'api.log'	);	
//file_put_contents( ABSPATH.'api.log', time()."rhc_supe_query::get_events\n".print_r( $r,true) );		
		extract( $r, EXTR_SKIP );
//error_log( "\nFEED:$feed\n", 3, ABSPATH.'api.log'	);	
//error_log( print_r($_REQUEST,true)."\n", 3, ABSPATH.'api.log'	);	
//error_log( print_r($atts,true)."\n", 3, ABSPATH.'api.log'	);	
		$events_reverse = false;
		//-------------
		$order = 'ASC'==strtoupper($order)? 'ASC' : 'DESC';
		$parse_taxonomy = '1'==$parse_taxonomy?true:false;
		//-------------
		$date_oper 		= '>=';
		$date_end_oper 	= '<=';
		//-------------
		if( '-1' == $number ){
			$limit = '';//no limit !!
		}else{
			$page 	= intval($page);
			$number = intval($number);
			
			if( $page < 0 ){
				//interchange operators
				$date_oper = '<';
				$date_end_oper = '>';
				$page = abs( $page ) -1;
				
				$events_reverse = true;
				
				$order = 'ASC' == $order ? 'DESC' : 'ASC' ;
			}
//error_log( "\nPAGE:$page NUMBER: $number\n", 3, ABSPATH.'api.log'	);				
			if( $page==0 ){
				$limit = "LIMIT $number";	
			}else if( 2==intval($feed) ){		
				$offset = $number * ( $page + 1 );
				$limit = "LIMIT $offset";				
			}else{
				$offset = $number * $page;
				$limit = "LIMIT $offset,$number";	
			}
	
		}
		//-----
		$allowed_date_oper = array(
			">",
			">=",
			"<",
			"<="
		);
	
		$date_compare = html_entity_decode( $date_compare );
		$date_end_compare = html_entity_decode( $date_end_compare );
	
		if( !empty( $date_compare ) && in_array( $date_compare, $allowed_date_oper ) ){
			$date_oper = $date_compare;
		}
		if( !empty( $date_end_compare ) && in_array( $date_end_compare, $allowed_date_oper ) ){
			$date_end_oper = $date_end_compare;		
		}
		//-----
		//-------------- build taxonomy/term filter
		if( '1'==$auto && is_tax() ){
			$args['taxonomy'] = get_query_var( 'taxonomy' );
			$args['terms'] = get_query_var( 'term' );
		}
		
		$taxonomy = isset($args['taxonomy'])?$args['taxonomy']:false;
		if(false!==$taxonomy){
			$taxonomy=explode(',',$taxonomy);
			$taxonomy_arr = array();
			foreach($taxonomy as $slug){
				if(empty($slug))continue;
				$taxonomy_arr[]=sprintf("'%s'", trim( $slug ) );
			}
			$taxonomy = implode(",",$taxonomy_arr);
		}

		$terms = isset($args['terms']) && !empty($args['terms']) ? $args['terms'] : false;
		if(false!==$terms){
			$terms=explode(',',$terms);
			$tmp = array();
			foreach($terms as $slug){
				$tmp[]=sprintf("'%s'", trim($slug) );
			}
			//--
	
			if( intval( $terms_children ) ){
				foreach( $taxonomy_arr as $t ){

					foreach( $tmp as $tmp_term ){
						$taxonomy_slug = trim( $t, "'" );
						$term_o = get_term_by( "slug", trim($tmp_term,"'"), $taxonomy_slug );
						if( is_object( $term_o ) && 'WP_Term' == get_class( $term_o )  ){
							$child_terms = get_term_children( $term_o->term_id, $taxonomy_slug );
							if( is_array( $child_terms ) && count( $child_terms ) > 0 ){
								foreach( $child_terms as $child_term_id ){
									$child_term_o = get_term_by( 'id', $child_term_id, $taxonomy_slug );
									$tmp[]=sprintf("'%s'", $child_term_o->slug );
								}
							}
						}
					}
				}
				
			}
		
			$terms = implode(",",$tmp);		
		}
		
		$having = '';
		
		$groupby_arr = array();
		
		$taxonomy_group_by = false;
		if( false===$taxonomy || false===$terms ){
			$taxonomy_tables = "";
			$taxonomy_filters = "";
		}else{
			$taxonomy_tables = "INNER JOIN $wpdb->term_relationships R ON R.object_id=E.post_id
INNER JOIN $wpdb->term_taxonomy TT ON TT.term_taxonomy_id=R.term_taxonomy_id
INNER JOIN $wpdb->terms T ON T.term_id=TT.term_id";

			$taxonomy_filters= "AND TT.taxonomy IN ($taxonomy)
AND T.slug IN ($terms)";	

			$taxonomy_group_by="CONCAT(E.event_start,E.post_id)";
		}	

		//------------
		if( !empty( $tax_and_filter ) ){
			//alternative to the orginal tax/term pair				
			$tax_and_filter_taxonomies = array();
			$tax_and_filter_terms = array();
			if( $taf_taxonomies = explode(';', $tax_and_filter) ){
				if( is_array( $taf_taxonomies ) && count( $taf_taxonomies ) > 0 ){
					foreach( $taf_taxonomies as $str ){ //str = calendar:debug,theater
						if( ''==trim($str) ) continue;//extra ; at the end
						if( $arr = explode( ':', $str ) ){
							if( is_array( $arr ) && count( $arr ) == 2 ){
								$tax_and_filter_taxonomies[] = $arr[0];
								if( $brr = explode(',', $arr[1]) ){//$arr[1]=debug,theater
									if( is_array( $brr ) && count( $brr ) > 0 ){
										foreach( $brr as $taf_term ){
											if( '' == trim( $taf_term ) ) continue; //extra , at the end of terms list
											$tax_and_filter_terms[] = $taf_term;
										}
									}
								}	
							}
						}
					}
				}
			}
			
			if( count($tax_and_filter_taxonomies) > 0 && count( $tax_and_filter_terms ) > 0 ){
				
				$taf_taxonomies = array();
				$taf_terms = array();
				foreach( $tax_and_filter_taxonomies as $taf_taxonomy ){
					$taf_taxonomies[]= sprintf("'%s'",$taf_taxonomy);
				}
				foreach( $tax_and_filter_terms as $taf_term ){
					$taf_terms[]= sprintf("'%s'",$taf_term);
				}
				
				$taxonomy_tables = "INNER JOIN $wpdb->term_relationships R ON R.object_id=E.post_id
	INNER JOIN $wpdb->term_taxonomy TT ON TT.term_taxonomy_id=R.term_taxonomy_id
	INNER JOIN $wpdb->terms T ON T.term_id=TT.term_id";

				$taxonomy_filters= sprintf("AND TT.taxonomy IN (%s)
AND T.slug IN (%s)",
					implode(',',$taf_taxonomies),
					implode(',',$taf_terms)
				);	

				$taxonomy_group_by = "CONCAT(E.event_start,E.post_id)";				
				
				$having = sprintf("HAVING COUNT(*)>=%d", count( array_unique( $taf_terms ) ) );
			}		
		}

		if( false!== $taxonomy_group_by ){
			$groupby_arr[]=$taxonomy_group_by;		
		}		
		//------------
		
		$filters = "";
		//--- EVENT Date filters
		
		$date_filter = "";
		if(!empty($date)){
			$date = 'NOW'==$date ? current_time('mysql') : $date;
			$ts = strtotime($date);			
			//-- handle local tz diference
			if( 1 == intval( $local_tz ) && 0 != intval( $tz_client_server_difference ) ){
//				$ts = $ts - ( $tz_client_server_difference * 3600 );
			}
			//--end
			$format = $horizon=='day' ? 'Y-m-d' : 'Y-m-d H:i:s' ;
			
			$db_field = 'E.event_start';
			if( $horizon=='end' ){
				$db_field = 'E.event_end';
			}
			
			if(false!==$ts){
				$date_filter.= sprintf(" AND (%s %s '%s')",
					$db_field,
					$date_oper, // >=
					date($format,$ts)
				);
			}
		}	
		
		if(!empty($date_end)){
			$date_end = 'NOW'==$date_end ? current_time('mysql') : $date_end;

			if(strlen($date_end)<=10){
				$date_end = date('Y-m-d 23:59:59', strtotime($date_end) );
			}
			
			$ts = strtotime($date_end);
			//$format = $horizon=='day' ? 'Y-m-d' : 'Y-m-d H:i:s' ;
			if( 1 == intval( $local_tz ) && 0 != intval( $tz_client_server_difference ) ){
//				$ts = $ts - ( $tz_client_server_difference * 3600 );
			}
			//--end			
			$format = 'Y-m-d H:i:s' ;
		
			if(false!==$ts){
				$date_filter.= sprintf(" AND (E.event_end %s '%s')", 
					$date_end_oper, // <=
					date($format,$ts)
				);
			}
		}
		
		if(!empty($dayspast)){
			if( false===$date || empty( $date ) ){
				$ts = mktime(0,0,0,date('m'),date('d')-$dayspast,date('Y')) ;
			}else{
				$ts = mktime(0,0,0,date('m', $ts),date('d', $ts)-$dayspast,date('Y', $ts)) ;
			}
			$format = $horizon=='day' ? 'Y-m-d' : 'Y-m-d H:i:s' ;
			if(false!==$ts){
				$date_filter.= sprintf(" AND (E.event_start >= '%s')",
					date($format,$ts)
				);
			}
		}
	
		//-- by specific post_id
		if( 'current' == $post_id ){
			$post_id = get_the_ID();
		}
		if( is_numeric( $post_id ) ){
			$filters.= " AND(E.post_id=" . intval( $post_id ) . ")";
		}
		//-- intended for use with post_ID, not alone, althought you can.
		if( !empty( $rdate ) && is_numeric( $rdate ) ){
			$filters.= sprintf(" AND(E.event_start='%s')", $rdate);
		}
		
		//-- Premiere
		if('1'==$premiere){
			$filters.=" AND(E.number=0)";
		}else if('2'==$premiere){
			$groupby_arr[]="E.post_id";
		}
		
		//-- ALL day filter --------------------
		if(!empty($allday)){
			$allday = intval($allday) ? 1 : 0;
			$filters.=" AND(E.allday=$allday)";
		}
		
		//-- maybe csv values: Post status, post type
		foreach( array(
				'post_status' => 'P.post_status',
				'post_type'	 => 'P.post_type'
			) as $field => $sql_field ){
			if(!empty($$field)){
				$sql_val = rhc_supe_query::csv_to_sql_strings( $$field );
				$filters.=sprintf(" AND(%s IN (%s))",$sql_field,$sql_val);
			}
		}
		//--- author filter
		$filters.=rhc_supe_query::get_author_sql_filter( $author );
		
		//--- near your location filter
		$filters .= rhc_supe_query::filter_geo_radius( $r, $args, $atts );
		
		$groupby = empty( $groupby_arr ) ? '' : sprintf("GROUP BY %s", implode(',', $groupby_arr) );
		//----------------------
		$sql = "SELECT E.*,P.*
FROM `{$wpdb->prefix}rhc_events` E
INNER JOIN $wpdb->posts P ON P.ID=E.post_id
$taxonomy_tables
WHERE (1) 
$date_filter
$taxonomy_filters
$filters
$groupby
$having
ORDER BY E.event_start $order
$limit
";
		
if(isset($_REQUEST['rhc_debug']) && current_user_can('manage_options')){
echo "SQL:$sql<br><-----";
}		
//error_log($sql."\n\n",3,ABSPATH.'api.log');
		$events = array();
//error_log( "\n"."\n".$sql."\n", 3, ABSPATH.'api.log' );			
		if( '1'!=$feed && $wpdb->query($sql) ){	
			$events = rhc_supe_query::handle_get_taxonomy_and_terms( $wpdb->last_result, $parse_taxonomy, $parse_taxonomymeta, $atts );	
			if( $events_reverse ){
//file_put_contents( 	ABSPATH.'api.log',print_r( $events,true) );	
				$events = array_reverse( $events );	
			}				
		}
		
		//permalink
		$events = rhc_supe_query::handle_get_permalink( $events, $atts );
		
		//fill post meta
		$events = rhc_supe_query::handle_get_postmeta( $events, $atts );
		
		//fill in images
		$events = rhc_supe_query::handle_get_images( $events, $atts );
		
		if( '0'!=$feed ){
			$taxonomy = isset($args['taxonomy'])?$args['taxonomy']:'';
			$terms = isset($args['terms']) && !empty($args['terms']) ? $args['terms'] : '';			
			$json_feed = apply_filters('rhc_json_feed',false,$taxonomy,$terms,(is_numeric($author)?$author:''),(is_string($author)?$author:''));		
		}else{
			$json_feed = array();
		}

		/*
		if(true||$feed!='0'){
			if(!empty($calendar)){
				$json_feed = apply_filters('rhc_json_feed',false,RHC_CALENDAR,$calendar,$author,$author_name);	
			}else{
				$json_feed = apply_filters('rhc_json_feed',false,$taxonomy,$terms,$author,$author_name);	
			}	
		}
		*/
		
		if( !empty($events) ){
			$calendar_url = isset( $atts['calendar_url'] ) && !empty( $atts['calendar_url'] ) ? $atts['calendar_url'] : false ;
			if( false!==$calendar_url ){
				foreach( $events as $i => $e ){
					$e->the_permalink = $calendar_url;
				}
			}		
		}
//file_put_contents( ABSPATH.'api.log', "COUNT:".count($events)."\nnumber:$number\npage:$page\nOFF:".$page*$number );			
		$events = apply_filters('rhc_supe_get_events', $events, compact('args','atts','json_feed','date','date_end') );	

		if( $page > 0 ){
			if( 2==intval( $feed ) ){
				$events = array_slice( $events, $page * $number, $number );
			}else if( 1==intval( $feed )  ){
				$events = array_slice( $events, $page * $number, $number );
			}
		}
		return $events;
	}

	public static function handle_get_permalink( $events, $atts ){
		if( empty( $events ) ) return $events;
		foreach($events as $i => $e){
			$e->the_permalink = get_the_permalink( $e->ID );
		}
		return $events;
	}
	
	public static function handle_get_postmeta( $events, $atts ){
		$parse_postmeta = isset( $atts['parse_postmeta'] ) ? $atts['parse_postmeta'] : '' ;
		if( empty( $parse_postmeta ) ) return $events;
		if( empty( $events ) ) return $events;
		
		$fields = explode( ',', $parse_postmeta );
		foreach($events as $i => $e){
			if( !property_exists( $e, 'meta' ) ){
				$e->meta = array();
			}
			//--
			if( $e->ID > 0 ){
				foreach( $fields as $field ){
					$v = get_post_meta( $e->ID, $field, true );
					//-- 
					if( in_array( $field, array('fc_color','fc_text_color') ) ){
						$v = '#'==$v ? '' : $v ;
					}
					$e->meta[ $field ] = $v;
				}			
			}
		}
		return $events;
	}
	
	public static function handle_get_images( $events, $atts ){
		if( empty( $events ) ) return $events;
		$tools = new calendar_ajax;
		$images = apply_filters( 'rhc_images', array('rhc_top_image','rhc_dbox_image','rhc_tooltip_image','rhc_month_image') );
		foreach( $events as $i => $e ){
			if( !property_exists( $e, 'images' ) ){
				$events[$i]->images = array();
			}
		
			foreach( $images as $meta_key ){
				$attachment_id = get_post_meta( $e->ID, $meta_key, true );

				$size = apply_filters( 'supe_handle_get_images', 'full', $atts, $meta_key );
				$image = $tools->get_tooltip_image($e->ID, $attachment_id, $size);

				if(is_array($image)&&isset($image[0])){		
					$events[$i]->images[$meta_key] = $image[0];
				}			
			}				
		}
		return $events;
	}
	
	public static function handle_get_taxonomy_and_terms( $events, $parse_taxonomy=false, $parse_taxonomymeta=false, $atts=array() ){
		if(!$parse_taxonomy)return $events;
		if(empty($events))return $events;
		$parse_taxonomymeta = '1'==$parse_taxonomymeta?true:false;
		if($parse_taxonomymeta){
			global $wpdb,$rhc_plugin;
			if( $rhc_plugin->wp44plus ){
				$meta_fields = $wpdb->get_col("SELECT DISTINCT(meta_key) FROM `{$wpdb->prefix}termmeta`;",0); 
			}else{
				$meta_fields = $wpdb->get_col("SELECT DISTINCT(meta_key) FROM `{$wpdb->prefix}taxonomymeta`;",0); 
			}
			
			$meta_fields = is_array($meta_fields)?$meta_fields:array();
			if(empty($meta_fields)){
				$parse_taxonomymeta=false;
			}
		}

		//-----
		foreach($events as $i => $post){
			$events[$i]->taxonomies = array();
			$taxonomies = get_object_taxonomies(array('post_type'=>$post->post_type),'objects');
			if(!empty($taxonomies)){
				foreach($taxonomies as $taxonomy => $tax){
					$terms = wp_get_post_terms( $post->ID, $taxonomy );
				
					if( is_array($terms) && count($terms)>0 ){
						
						if( $parse_taxonomymeta ){
							foreach($terms as $j => $term){

								//--term url is missing
								$term->term_link = get_term_link( $term, $taxonomy );

								$term->meta = array();
								foreach($meta_fields as $meta_field){
									$value = get_term_meta( $term->term_id, $meta_field, true );
									if(!empty($value)){
										$term->meta[ $meta_field ] = $value;
									}
								}
								
							}
						}
						
						$tax = clone $tax;
						$tax->terms = $terms;	
						$events[$i]->taxonomies[]=$tax;
					
					}
				}
			}
		}
	
		return $events;
	}
	
	public static function handle_get_event_terms( $events, $parse_taxonomy=false, $atts=array() ){
		if(!$parse_taxonomy)return $events;
		if(empty($events))return $events;
		foreach($events as $i => $post){
			//----
			$taxonomies = get_object_taxonomies(array('post_type'=>$post->post_type),'objects');
			if(!empty($taxonomies)){
				foreach($taxonomies as $taxonomy => $tax){
					$terms = wp_get_post_terms( $post->ID, $taxonomy );
					if(is_array($terms) && count($terms)>0){
						foreach($terms as $term){
//								$url = get_term_meta($term->term_id,'website',true);
//								$url = trim($url)==''?get_term_meta($term->term_id,'url',true):$url;
//								$url = trim($url)==''?get_term_link( $term, $taxonomy ):$url;
							$url = get_term_link( $term, $taxonomy );
							$gaddress = get_term_meta($term->term_id,'gaddress',true);
							$color = get_term_meta($term->term_id,'color',true);
							$bg = get_term_meta($term->term_id,'background_color',true);
							$image = get_term_meta($term->term_id,'image',true);
							if( empty($image) && function_exists('get_term_thumbnail') ){
								$term_thumbnail_id = get_term_thumbnail_id( $term->term_id );
								$src = wp_get_attachment_image_src( $term_thumbnail_id, 'full' );
								if( isset( $src[0] ) ){
									$image = $src[0];
								}
							}								
							
							$glat = get_term_meta($term->term_id,'glat',true);
							$glon = get_term_meta($term->term_id,'glon',true);
							$ginfo = get_term_meta($term->term_id,'ginfo',true);
							
							$new = (object)array(
								'term_id'=>$term->term_taxonomy_id,
								'taxonomy'=>$taxonomy,
								'taxonomy_label'=>$tax->labels->singular_name,
								'slug'=>$term->slug,
								'name'=>$term->name,
								'url'=>$url,
								'gaddress'=>$gaddress,
								'glat'	=> $glat,
								'glon'	=> $glon,
								'ginfo'	=> $ginfo,
								'color'=>$color,
								'background_color'=>$bg,
								'image'=>$image
							);
							
							foreach(array('address','city','state','zip','country') as $meta){
								$new->$meta = get_term_meta($term->term_id,$meta,true);
							}
							
							if(!property_exists($events[$i],'terms')){
								$events[$i]->terms = array();	
							}
							$events[$i]->terms[]=apply_filters('rhc_event_term_meta', $new, $term->term_id, $taxonomy);
						}						
					}
				}
			}
			//----		
		}

		return $events;
	}
	
	public static function get_author_sql_filter( $author ){
		if(''==trim($author)){
			return '';
		}else if('current'==$author){//this locks a username named current.
			if( is_user_logged_in() ){
				global $userdata;
				return sprintf(" AND( P.post_author=%s)", $userdata->ID);
			}else{
				return ' AND(0)';//force nothing as user is not logged.
			}
		}else{
			$arr = explode(',', $author);
			$tmp = array();
			if(is_array($arr) && count($arr)>0){
				foreach($arr as $arr_author){
					if( is_numeric( $arr_author ) ){
						$tmp[]=$arr_author;
					}else{
						if($author_id=rhc_supe_query::get_author_id( $arr_author )){
							$tmp[]=$author_id;
						}
					}
				}
			}
			if(count($tmp)>0){
				return sprintf(" AND( P.post_author IN (%s) )", implode(",",$tmp));
			}
		}
		return '';
	}
	
	public static function get_author_id( $author ){
		global $wpdb;
		$sql = sprintf("SELECT COALESCE((SELECT ID FROM $wpdb->users WHERE user_login LIKE \"%s\" LIMIT 1),'')",addslashes(stripslashes($author)));
		return $wpdb->get_var($sql,0,0);
	}
	
	public static function csv_to_sql_strings( $str ){
		$arr = explode(',',$str);
		$tmp = array();
		foreach($arr as $value){
			$tmp[]=sprintf("'%s'",addslashes($value));
		}
		return implode(',',$tmp);
	}	
	
	public static function filter_geo_radius( $r, $args, $atts ){
//error_log( 'filter_geo_radius:'.print_r($r,true)."\n", 3, ABSPATH.'api.log'	);	
		global $rhc_plugin;
		
		if( isset( $r['taxonomy'] ) ){
			$taxonomies = explode( ',', str_replace( ' ', '', $r['taxonomy'] ) );
			if( in_array( RHC_VENUE, $taxonomies ) ){
				//a venue has being selected in the dropdown, do not include other venues near you.
				return '';
			}
		}

		$filter = '';
		if( isset( $args['geo_radius'] ) && is_numeric( $args['geo_radius'] ) && $args['geo_radius'] > 0 ){
			$distance_unit = $rhc_plugin->get_option( 'rhc_distance_unit', 'mi', true );
			//conver geo_radius to degrees:
			//69 miles (111 kilometers)
			if( 'km' == $distance_unit ){
				$args['geo_radius'] = $args['geo_radius'] / 111 ;
			}else{
				//miles.
				$args['geo_radius'] = $args['geo_radius'] / 69 ;
			}
					
			if( taxonomy_exists( RHC_VENUE ) ){
				//'28.3752175,-81.54947199999998';
				if( isset( $args['geo_center'] ) && !empty( $args['geo_center'] ) ){
					$coord = explode(',', str_replace( ' ', '', $args['geo_center'] ) );
					$lat = $coord[0];
					$lon = $coord[1];
				}else{
					if( $coord = apply_filters( 'rhc_get_geo_location', false ) ){
					//if( $coord = apply_filters( 'rhc_get_geo_location', array('28.3752175','-81.54947199999998') ) ){
//error_log( "LINE:" . __LINE__, 3, ABSPATH.'api.log' );
						$lat = $coord[0];
						$lon = $coord[1];
					}else{
//error_log( "LINE:" . __LINE__, 3, ABSPATH.'api.log' );
						if( function_exists( 'geoip_detect2_get_info_from_current_ip' ) ){
							try {
								$result = geoip_detect2_get_info_from_current_ip();
								
								$lat = $result->location->latitude;
								$lon =  $result->location->longitude;
							}catch( Exception $e ){
								return '';
							}

							if( empty( $lat ) || empty( $lon ) ){
								return '';
							}

						}else{
							return '';
						}
					}
				}
								
				$taxonomy = RHC_VENUE;
//error_log( "LATLON:".$lat.','.$lon . "\n" ,3, ABSPATH.'api.log' ); 
				$lat_a = (float)$lat - (float)$args['geo_radius'];
				$lat_b = (float)$lat + (float)$args['geo_radius'];
				$lon_a = (float)$lon - (float)$args['geo_radius'];
				$lon_b = (float)$lon + (float)$args['geo_radius'];
				//NOTE: may fail near the antimeridian
				$query = array(
						'taxonomy' => $taxonomy,
						'hide_empty' => false,
						'post_type'	=> array('events'),
						'orderby'	=> 'name',
						'meta_query' => array(
							 array(
								'key'       => 'glat',
								'value'     => $lat_a,
								'type'		=> 'DECIMAL(18,15)',
								'compare'   => '>'
							 ),
							 array(
								'key'       => 'glat',
								'value'     => $lat_b,
								'type'		=> 'DECIMAL(18,15)',
								'compare'   => '<'
							 ),
							 array(
								'key'       => 'glon',
								'value'     => $lon_a,
								'type'		=> 'DECIMAL(18,15)',
								'compare'   => '>'
							 ),
							 array(
								'key'       => 'glon',
								'value'     => $lon_b,
								'type'		=> 'DECIMAL(18,15)',
								'compare'   => '<'
							 )
						)
					);
				//									
//error_log( print_r( $query, true ) . "\n" ,3, ABSPATH.'api.log' ); 						
//error_log( "XX".print_r( $args, true ) . "\n" ,3, ABSPATH.'api.log' ); 

				$raw_terms = get_terms( $taxonomy, $query );	
			
				if( is_array( $raw_terms ) && count( $raw_terms ) > 0 ){
					$arr = array();
					foreach( $raw_terms as $t ){
						$arr[] = sprintf( "'%s'", $t->slug );
					}
					global $wpdb;
					$filter = sprintf(" AND(E.post_id IN (SELECT R.object_id FROM `{$wpdb->term_relationships}` R INNER JOIN `{$wpdb->term_taxonomy}` TT ON TT.term_taxonomy_id=R.term_taxonomy_id INNER JOIN `{$wpdb->terms}` T ON T.term_id=TT.term_id WHERE TT.taxonomy='%s' AND T.slug IN (%s)) )",
						RHC_VENUE,
						implode(',',$arr)
					);
					//
				}else{
					//force empty.
					return ' AND(0) ';
				}
				
//error_log( 'RADIUS ' .  $args['geo_radius'] . "\n" ,3, ABSPATH.'api.log' );
//error_log( print_r( $raw_terms, true ) . "\n" ,3, ABSPATH.'api.log' );
//error_log( "ZZ".print_r( $args, true ) . "\n" ,3, ABSPATH.'api.log' ); 
			}
		}	
				
		return $filter;
	}
}

?>