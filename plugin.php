<?php
/**
 * Plugin Name: HKIH SportsLocations
 * Plugin URI: https://github.com/devgeniem/client-hkih
 * Description: HKIH SportsLocations functionality
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Author: Geniem Oy
 * Author URI: https://geniem.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: hkih-sports-locations
 * Domain Path: /languages
 */

use HKIH\SportsLocations\SportsLocationsPlugin;

// Check if Composer has been initialized in this directory.
// Otherwise, we just use global composer autoloading.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Get the plugin version.
$plugin_data    = get_file_data( __FILE__, [ 'Version' => 'Version' ], 'plugin' );
$plugin_version = $plugin_data['Version'];

$plugin_path = __DIR__;

// Initialize the plugin.
SportsLocationsPlugin::init( $plugin_version, $plugin_path );

if ( ! function_exists( 'hkih_sports_locations' ) ) {
    /**
     * Get the {{plugin-name}} plugin instance.
     *
     * @return SportsLocationsPlugin
     */
    function hkih_sports_locations() : SportsLocationsPlugin {
        return SportsLocationsPlugin::plugin();
    }
}
