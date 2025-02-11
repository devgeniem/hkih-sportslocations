<?php
/**
 * SelectedLocations ACF Layout
 */

namespace HKIH\SportsLocations\ACF;

use Closure;
use Exception;
use Geniem\ACF\Field;
use Geniem\Theme\Logger;
use Geniem\Theme\Utils;
use HKIH\SportsLocations\ACF\Fields;
use function add_filter;
use function apply_filters;

/**
 * Class SelectedLocationsLayout
 *
 * @package HKIH\SportsLocations\ACF
 */
class SelectedLocationsLayout extends Field\Flexible\Layout {

    /**
     * Layout key
     */
    const KEY = '_locations_selected';
    /**
     * GraphQL Layout Key
     */
    const GRAPHQL_LAYOUT_KEY = 'LocationsSelected';
    /**
     * Translations.
     *
     * @var array[]
     */
    private array $strings;

    /**
     * SelectedLocationsLayout constructor.
     *
     * @param $key
     */
    public function __construct( $key ) {
        $key = $key . self::KEY;

        parent::__construct( 'Sports Locations', $key, 'locations_selected' );

        $this->strings = [
            'title'             => [
                'label'        => __( 'Title', 'hkih-sports-locations' ),
                'instructions' => '',
            ],
            'language'          => [
                'label'        => __( 'Language', 'hkih-sports-locations' ),
                'instructions' => '',
            ],
            'search'            => [
                'label'        => __( 'Search term', 'hkih-sports-locations' ),
                'instructions' => '',
            ],
            'result_count'      => [
                'label'        => __( 'Result count', 'hkih-sports-locations' ),
                'instructions' => '',
            ],
            'location_selector' => [
                'label'        => __( 'Select Sports Locations', 'hkih-sports-locations' ),
                'instructions' => '',
            ],
        ];

        $this->add_selection_fields();

        add_action(
            'graphql_register_types',
            Closure::fromCallable( [ $this, 'register_graphql_fields' ] ),
            9
        );
    }

    private function add_selection_fields() : void {
        $key = $this->get_key();

        try {
            $title_field = ( new Field\Text( $this->strings['title']['label'] ) )
                ->set_key( "${key}_title" )
                ->set_name( 'title' )
                ->set_default_value( 'TODO: Default value' )
                ->set_wrapper_width( 80 )
                ->set_instructions( $this->strings['title']['instructions'] );

            if ( function_exists( 'pll_default_language' ) ) {
                $language_field = ( new Field\Text( $this->strings['language']['label'] ) )
                    ->set_key( "${key}_language" )
                    ->set_name( 'language' )
                    ->set_default_value( pll_default_language() )
                    ->set_wrapper_width( 20 )
                    ->set_readonly()
                    ->set_instructions( $this->strings['language']['instructions'] );
            }

            $search_field = ( new Field\Text( $this->strings['search']['label'] ) )
                ->set_key( "${key}_search" )
                ->set_name( 'search' )
                ->set_wrapper_width( 80 )
                ->set_instructions( $this->strings['search']['instructions'] );

            $result_count_field = ( new Field\Text( $this->strings['result_count']['label'] ) )
                ->set_key( "${key}_result_count" )
                ->set_name( 'result_count' )
                ->set_readonly()
                ->set_wrapper_width( 20 )
                ->set_instructions( $this->strings['result_count']['instructions'] );

            $location_selector = ( new Fields\AcfCodifierLocationRestRelationship(
                $this->strings['location_selector']['label']
            ) )
                ->set_key( "${key}_selected_locations" )
                ->set_name( 'selected_locations' )
                ->update_value( fn( $values, $post_id, $field, $raw ) => $raw )
                ->set_instructions( $this->strings['location_selector']['instructions'] );

            $this->add_fields( [
                $title_field,
                $language_field ?? null,
                $search_field,
                $result_count_field,
                $location_selector,
            ] );
        }
        catch ( Exception $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTraceAsString() );
        }
    }

    /**
     * Register Layout fields to GraphQL.
     */
    public function register_graphql_fields() : void {
        $key = self::GRAPHQL_LAYOUT_KEY;

        // If the layout is already known/initialized, no need to register it again.
        if ( array_key_exists( $key, apply_filters( 'hkih_graphql_layouts', [] ) ) ) {
            return;
        }

        $fields = [
            'title'     => [
                'type'        => 'String',
                'description' => __( 'Module title', 'hkih-sports-locations' ),
            ],
            'module'    => [
                'type'        => 'String',
                'description' => __( 'Module type', 'hkih-sports-locations' ),
            ],
            'locations' => [
                'type'        => [ 'list_of' => 'Int' ],
                'description' => __( 'List of location IDs', 'hkih-sports-locations' ),
            ],
        ];

        add_filter( 'hkih_graphql_layouts', Utils::add_to_layouts( $fields, $key ) );
        add_filter( 'hkih_graphql_modules', Utils::add_to_layouts( $fields, $key ) );
        add_filter( 'hkih_posttype_collection_modules', Utils::add_to_layouts( $key, $key ) );
        add_filter( 'hkih_posttype_page_graphql_modules', Utils::add_to_layouts( $fields, $key ) );
        add_filter( 'hkih_posttype_post_graphql_modules', Utils::add_to_layouts( $fields, $key ) );
    }
}
