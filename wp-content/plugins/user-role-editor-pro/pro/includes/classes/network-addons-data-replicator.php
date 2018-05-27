<?php
/*
 * Replicate addons data from the main site for all WordPress multisite network
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * 
*/

class Ure_Network_Addons_Data_Replicator {

    private $lib = null;
    private $addons = null;
    
    public function __construct() {
        $this->lib = URE_Lib_Pro::get_instance();
    }
    // end of __construct()

    
    private function save_data_to_subsite($options_table_name, $addon_id) {
        global $wpdb;
        
        $access_data_key = $this->addons[$addon_id]->access_data_key;
        $data = $this->addons[$addon_id]->data;
        
        $query1 = $wpdb->prepare(
                "SELECT option_id FROM {$options_table_name} WHERE option_name=%s limit 0,1",
                array($access_data_key)
                        );
        $option_id = $wpdb->get_var($query1);
        if ($option_id>0) {
            $query = $wpdb->prepare(
                        "UPDATE {$options_table_name}
                            set option_value=%s
                            WHERE option_id=%d 
                            LIMIT 1",
                        array($data, $option_id)
                                );
        } else {
            $query = $wpdb->prepare(
                        "INSERT INTO {$options_table_name}
                            SET option_name=%s,
                                option_value=%s",
                        array($access_data_key, $data)
                                );
        }
        $wpdb->query($query);
        if ($wpdb->last_error) {
            return false;
        }
        
        return true;
    }
    // end of save_widgets_access_data_to_subsite()


    private function get_data_from_main_blog($access_data_key) {
    
        $data = get_option($access_data_key);
        $serialized_data = serialize($data);
        
        return $serialized_data;
        
    }
    // end of get_data_from_main_blog()
    
    
    private function what_to_replicate() {
        $addons_manager = URE_Addons_Manager::get_instance();
        $all_addons = $addons_manager->get_active();
        $this->addons = array();
        foreach($all_addons as $addon) {
            $replicator_id = URE_Addons_Manager::get_replicator_id($addon->id);
            $replicate = filter_input(INPUT_POST, $replicator_id, FILTER_SANITIZE_NUMBER_INT);
            if ($replicate) {
                $addon->data = $this->get_data_from_main_blog($addon->access_data_key);
                $this->addons[$addon->id] = $addon;
            }
        }
                
    }
    // end of what_to_replicate()
    
    
    public function act() {
        global $wpdb;
        
        $this->what_to_replicate();                                                        
        
        $blog_ids = $this->lib->get('blog_ids');
        foreach ($blog_ids as $blog_id) {
            $prefix = $wpdb->get_blog_prefix($blog_id);
            $options_table_name = $prefix . 'options';            
            foreach($this->addons as $addon) {
                $result = $this->save_data_to_subsite($options_table_name, $addon->id);
                if (!$result) {
                    return false;
                }
            }
        }   
        
        return true;        
    }
    // end of act()
}
// end of Ure_Network_Addons_Data_Replicator class