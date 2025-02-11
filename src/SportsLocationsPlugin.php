<?php
/**
 * This file initializes all plugin functionalities.
 */

namespace HKIH\SportsLocations;

use Geniem\ACF\Exception;
use Geniem\ACF\Field\FlexibleContent;
use Geniem\Theme\Logger;
use HKIH\SportsLocations\ACF\SelectedLocationsLayout;
use HKIH\SportsLocations\ACF\SelectedLocationsCarouselLayout;

/**
 * Class SportsLocationsPlugin
 *
 * @package HKIH\SportsLocations
 */
final class SportsLocationsPlugin {

    /**
     * Holds the singleton.
     *
     * @var SportsLocationsPlugin
     */
    protected static $instance;

    /**
     * Current plugin version.
     *
     * @var string
     */
    protected $version = '';
    /**
     * The plugin directory path.
     *
     * @var string
     */
    protected $plugin_path = '';
    /**
     * The plugin root uri without trailing slash.
     *
     * @var string
     */
    protected $plugin_uri = '';

    /**
     * Initialize the plugin functionalities.
     *
     * @param string $version     The current plugin version.
     * @param string $plugin_path The plugin path.
     */
    protected function __construct( $version, $plugin_path ) {
        $this->version     = $version;
        $this->plugin_path = $plugin_path;
        $this->plugin_uri  = plugin_dir_url( $plugin_path ) . basename( $this->plugin_path );
    }

    /**
     * Get the instance.
     *
     * @return SportsLocationsPlugin
     */
    public static function get_instance() : SportsLocationsPlugin {
        return self::$instance;
    }

    /**
     * Initialize the plugin by creating the singleton.
     *
     * @param string $version     The current plugin version.
     * @param string $plugin_path The plugin path.
     */
    public static function init( $version, $plugin_path ) {
        if ( empty( self::$instance ) ) {
            self::$instance = new self( $version, $plugin_path );
            self::$instance->hooks();
        }
    }

    /**
     * Add plugin hooks and filters.
     */
    protected function hooks() : void {
        add_action(
            'acf/include_fields',
            [ $this, 'require_rest_relationship_field' ]
        );

        add_action(
            'admin_enqueue_scripts',
            [ $this, 'enqueue_admin_scripts' ]
        );

        add_action(
            'wp_ajax_locations_selected',
            [ $this, 'locations_selected_callback' ]
        );

        /**
         * Adds selected events REST API response.
         */
        add_filter(
            'hkih_rest_acf_collection_modules_layout_locations_selected',
            [ $this, 'locations_selected_rest_callback' ]
        );

        add_filter(
            'hkih_rest_acf_post_modules_layout_locations_selected',
            [ $this, 'locations_selected_rest_callback' ]
        );

        add_filter(
            'hkih_rest_acf_page_modules_layout_locations_selected',
            [ $this, 'locations_selected_rest_callback' ]
        );

        /**
         * Register Selected locations carousel REST API response.
         */
        add_filter(
            'hkih_rest_acf_collection_modules_layout_locations_selected_carousel',
            [ $this, 'locations_selected_carousel_rest_callback' ]
        );

        add_filter(
            'hkih_rest_acf_post_modules_layout_locations_selected_carousel',
            [ $this, 'locations_selected_carousel_rest_callback' ]
        );

        add_filter(
            'hkih_rest_acf_page_modules_layout_locations_selected_carousel',
            [ $this, 'locations_selected_carousel_rest_callback' ]
        );

        /**
         * Add Collection Modules to these module layouts.
         */
        add_filter(
            'hkih_acf_collection_modules_layouts',
            [ $this, 'add_collection_layouts' ]
        );

        add_filter(
            'hkih_acf_post_modules_layouts',
            [ $this, 'add_collection_layouts' ]
        );

        add_filter(
            'hkih_acf_page_modules_layouts',
            [ $this, 'add_collection_layouts' ]
        );
    }

    /**
     * Get the plugin instance.
     *
     * @return SportsLocationsPlugin
     */
    public static function plugin() : SportsLocationsPlugin {
        return self::$instance;
    }

    /**
     * Get the version.
     *
     * @return string
     */
    public function get_version() : string {
        return $this->version;
    }

    /**
     * Registers rest_relationship ACF Field.
     */
    public function require_rest_relationship_field() : void {
        require_once __DIR__ . '/ACF/Fields/AcfLocationFieldRestRelationship.php';
    }

    /**
     * Enqueue admin side scripts if they exist.
     */
    public function enqueue_admin_scripts() : void {
        $css_path = $this->get_plugin_path() . '/assets/dist/admin.css';
        $js_path  = $this->get_plugin_path() . '/assets/dist/admin.js';

        $css_mod_time = file_exists( $css_path ) ? filemtime( $css_path ) : $this->version;
        $js_mod_time  = file_exists( $js_path ) ? filemtime( $js_path ) : $this->version;

        if ( file_exists( $css_path ) ) {
            wp_enqueue_style(
                'hkih-sports-locations-admin-css',
                $this->get_plugin_uri() . '/assets/dist/admin.css',
                [],
                $css_mod_time,
                'all'
            );
        }

        if ( file_exists( $js_path ) ) {
            wp_enqueue_script(
                'hkih-sports-locations-admin-js',
                $this->get_plugin_uri() . '/assets/dist/admin.js',
                [ 'jquery', 'acf-input', 'underscore' ],
                $js_mod_time,
                true
            );
        }
    }

    /**
     * Get the plugin directory path.
     *
     * @return string
     */
    public function get_plugin_path() : string {
        return $this->plugin_path;
    }

    /**
     * Get the plugin directory uri.
     *
     * @return string
     */
    public function get_plugin_uri() : string {
        return $this->plugin_uri;
    }

    /**
     * Add collection layouts
     *
     * @param FlexibleContent $modules Flexible content object.
     *
     * @return FlexibleContent
     */
    public function add_collection_layouts( FlexibleContent $modules ) : FlexibleContent {
        try {
            $modules->add_layout( new SelectedLocationsLayout( $modules->get_key() ) );
            $modules->add_layout( new SelectedLocationsCarouselLayout( $modules->get_key() ) );
        }
        catch ( Exception $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTrace() );
        }

        return $modules;
    }

    /**
     * Event AJAX search callback
     */
    public function locations_selected_callback() : void {
        $params = $_GET['params']; // phpcs:ignore

        if ( empty( $params ) ) {
            wp_send_json_success( $_GET );

            return;
        }

        try {
            $response = self::locations_selected_fetch( $params );
            $response = self::locations_selected_callback_output( $response );

            wp_send_json_success( $response );
        }
        catch ( \Exception $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTraceAsString() );
            wp_send_json_error();
        }
    }

    /**
     * Format payload for Ajax.
     *
     * @param array $data SportsLocationsPlugin::locations_selected_fetch() output.
     *
     * @return array
     */
    public static function locations_selected_callback_output( array $data = [] ) : array {
        return [
            'results' => [
                [
                    'text'     => __( 'Results', 'hkih' ),
                    'children' => array_values( $data ) ?? [],
                ],
            ],
            'count'   => count( $data ?? [] ),
            'limit'   => 100,
        ];
    }

    /**
     * locations_selected API Query.
     *
     * @param array $params Fetching parameters.
     *
     * @return array|bool|mixed|string
     * @throws \JsonException Thrown if JSON from API has errors.
     */
    public static function locations_selected_fetch( array $params = [] ) {
        $cache_key = 'locations_selected_fetch_' . md5( json_encode( $params, JSON_THROW_ON_ERROR ) );
        $response  = wp_cache_get( $cache_key );

        if ( ! $response ) {
            $response = LocationSearch::do_query( $params['search'] ?? '' );
            wp_cache_set( $cache_key, $response, '', MINUTE_IN_SECONDS * 15 );
        }

        return LocationSearch::process_locations( $response );
    }

    /**
     * Event selected REST field layout callback
     *
     * @param array $layout ACF layout data.
     *
     * @return array
     */
    public function locations_selected_rest_callback( array $layout ) : array {
        if ( empty( $layout['selected_locations'] ) || ! is_array( $layout['selected_locations'] ) ) {
            $layout['selected_locations'] = [];
        }

        return [
            'title'     => esc_html( $layout['title'] ),
            'locations' => array_keys( $layout['selected_locations'] ?? [] ),
            'module'    => esc_html( $layout['acf_fc_layout'] ?? '' ),
        ];
    }

    /**
     * Event selected carousel REST field layout callback
     *
     * @param array $layout ACF layout data.
     *
     * @return array
     */
    public function locations_selected_carousel_rest_callback( array $layout ) : array {
        
        if ( empty( $layout['selected_locations'] ) || ! is_array( $layout['selected_locations'] ) ) {
            $layout['selected_locations'] = [];
        }

        return [
            'title'     => esc_html( $layout['title'] ),
            'locations' => array_keys( $layout['selected_locations'] ?? [] ),
            'module'    => esc_html( $layout['acf_fc_layout'] ?? '' ),
        ];
    }
    
}
