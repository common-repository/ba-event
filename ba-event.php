<?php
/**
 * @wordpress-plugin
 * Plugin Name:       BA Event
 * Plugin URI: https://wordpress.org/plugins/ba-event/
 * Description: Highly customizable Event Booking solution with management system for any event sites like tours, excursions, conferences, seminars, theme parties, etc.
 * Version:           1.0.0
 * Author:            Booking Algorithms
 * Author URI: http://ba-booking.com
 * Text Domain: ba-event
 * Domain Path: /languages/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

define( 'BA_EVENT_VERSION', '1.0.0' );
define( 'BA_EVENT_PLUGIN', __FILE__ );
define( 'BA_EVENT_PLUGIN_DIR', untrailingslashit( dirname( BA_EVENT_PLUGIN ) ) );
define( 'BA_EVENT_TEXTDOMAIN', 'ba-event' );

if ( file_exists(  BA_EVENT_PLUGIN_DIR . '/includes/plugins/cmb2/init.php' ) ) {
  require_once BA_EVENT_PLUGIN_DIR . '/includes/plugins/cmb2/init.php';
}

include_once BA_EVENT_PLUGIN_DIR . '/includes/functions.php';

include_once BA_EVENT_PLUGIN_DIR . '/ba-event-main.php';

include_once BA_EVENT_PLUGIN_DIR . '/classes/widget-youmaylike.php';

if ( is_admin() ) {
   	include_once BA_EVENT_PLUGIN_DIR . '/admin/admin.php';
}
