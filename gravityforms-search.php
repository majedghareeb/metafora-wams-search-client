<?php
/*
Plugin Name: WAMS Client Search Field
Plugin URI: https://www.rakami.net
Description: Searches selected post types after a user types, displaying results below field.
Version: 1.0
Author: Majed
Author URI: https://www.rakami.net
Text Domain: gravityforms-search
*/

define('WAMS_GF_SEARCH_VERSION', '1.0');
define('WAMS_GF_SEARCH_PATH', plugin_dir_path(__FILE__));
define('WAMS_GF_SEARCH_URL', plugin_dir_url(__FILE__));

add_action('gform_loaded', array('WAMS_GF_Search_Field_Bootstrap', 'load'), 5);
class WAMS_GF_Search_Field_Bootstrap
{

    public static function load()
    {

        if (!method_exists('GFForms', 'include_addon_framework')) {
            return;
        }

        require_once('class-clientseaarchfieldaddon.php');

        GFAddOn::register('WAMS_Search_Field_Addon');
    }
}
