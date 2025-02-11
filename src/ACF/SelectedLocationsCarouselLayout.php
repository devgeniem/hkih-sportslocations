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
 * Class SelectedLocationsCarouselLayout
 *
 * @package HKIH\SportsLocations\ACF
 */
class SelectedLocationsCarouselLayout extends Field\Flexible\Layout {

    /**
     * Layout key
     */
    const KEY = '_locations_selected_carousel';
    /**
     * GraphQL Layout Key
     */
    const GRAPHQL_LAYOUT_KEY = 'LocationsSelectedCarousel';
    /**
     * Translations.
     *
     * @var array[]
     */
    private array $strings;

    /**
     * SelectedLocationsCarouselLayout constructor.
     *
     * @param $key
     */
    public function __construct( $key ) {
        $key = $key . self::KEY;

        parent::__construct( 'Sports Locations carousel', $key, 'locations_selected_carousel' );

        $this->strings = [
            'title'             => [
                'label'        => __( 'Title', 'hkih-sports-locations' ),
            ],
            'language'          => [
                'label'        => __( 'Language', 'hkih-sports-locations' ),
            ],
            'search'            => [
                'label'        => __( 'Search term', 'hkih-sports-locations' ),
            ],
            'result_count'      => [
                'label'        => __( 'Result count', 'hkih-sports-locations' ),
            ],
            'location_selector' => [
                'label'        => __( 'Select Sports Locations', 'hkih-sports-locations' ),
            ],
        ];

        $this->add_selection_carousel_fields();

        add_action(
            'graphql_register_types',
            Closure::fromCallable( [ $this, 'register_graphql_fields' ] ),
            9
        );
    }

    private function add_selection_carousel_fields() : void {
        $key = $this->get_key();

        try {
            $title_field = ( new Field\Text( $this->strings['title']['label'] ) )
                ->set_key( "{$key}_title" )
                ->set_name( 'title' )
                ->set_default_value( 'TODO: Default value' )
                ->set_wrapper_width( 80 );

            if ( function_exists( 'pll_default_language' ) ) {
                $language_field = ( new Field\Text( $this->strings['language']['label'] ) )
                    ->set_key( "{$key}_language" )
                    ->set_name( 'language' )
                    ->set_default_value( pll_default_language() )
                    ->set_wrapper_width( 20 )
                    ->set_readonly();
            }

            $search_field = ( new Field\Text( $this->strings['search']['label'] ) )
                ->set_key( "{$key}_search" )
                ->set_name( 'search' )
                ->set_wrapper_width( 80 );

            $result_count_field = ( new Field\Text( $this->strings['result_count']['label'] ) )
                ->set_key( "{$key}_result_count" )
                ->set_name( 'result_count' )
                ->set_readonly()
                ->set_wrapper_width( 20 );

            $location_selector = ( new Fields\AcfCodifierLocationRestRelationship(
                $this->strings['location_selector']['label']
            ) )
                ->set_key( "{$key}_selected_locations" )
                ->set_name( 'selected_locations' )
                ->update_value( fn( $values, $post_id, $field, $raw ) => $raw );

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
