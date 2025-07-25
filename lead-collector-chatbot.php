<?php
/**
 * Plugin Name:     Lead Collector Chatbot
 * Plugin URI:      https://github.com/androwee/chatbot-lead-collector
 * Description:     Plugin chatbot dengan Tailwind CSS untuk mengumpulkan data pengunjung.
 * Version:         4.3.0
 * Author:          andro
 * Author URI:      https://github.com/androwee/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LCC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'LCC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once LCC_PLUGIN_PATH . 'includes/class-chatbot-core.php';

new Chatbot_Core();

register_activation_hook( __FILE__, [ 'Chatbot_Core', 'on_activation' ] );