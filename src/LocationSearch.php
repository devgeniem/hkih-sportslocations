<?php
/**
 * LocationSearch
 */

namespace HKIH\SportsLocations;

use Geniem\Theme\Logger;

/**
 * Class LocationSearch
 *
 * @package HKIH\SportsLocations
 */
class LocationSearch {

    /**
     * Do Location Search Query.
     *
     * @param string $search Search term
     *
     * @return array|\WP_Error
     * @throws \JsonException If API Response wasn't correctly formed.
     */
    public static function do_query( string $search = '' ) {
        $cache_key = 'locations_search_do_query_' . md5( $search );
        $response  = wp_cache_get( $cache_key );

        if ( ! $response ) {
            $body = '{"query": '
                    . json_encode(
                        self::build_query( $search ),
                        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                    )
                    . '}';

            $response = wp_remote_post(
                KUVA_UNIFIED_API,
                [
                    'method'  => 'POST',
                    'headers' => [
                        'Accept-Encoding' => 'gzip, deflate, br',
                        'Content-Type'    => 'application/json',
                        'Accept'          => 'application/json',
                        'Connection'      => 'keep-alive',
                        'DNT'             => '1',
                    ],
                    'body'    => $body,
                ]
            );

            // If it's an error, just return it without caching.
            if ( is_wp_error( $response ) ) {
                return $response;
            }

            wp_cache_set( $cache_key, $response, '', HOUR_IN_SECONDS );
        }

        return $response;
    }

    /**
     * Build our GraphQL Query.
     *
     * @param string $search Search Query Term
     *
     * @return string
     */
    public static function build_query( string $search = '' ) : string {
        $query = '
            { unifiedSearch(index: location, ontologyTreeIdOrSets: [551], text: "%s", first: 50) {
                edges { node { venue { meta { id } name { fi sv en } } } }
            } }
        ';

        return sprintf( $query, $search );
    }

    /**
     * Process locations from GraphQL.
     *
     * @param array|\WP_Error $response Response payload.
     *
     * @return API\Entities\Location[]|array
     */
    public static function process_locations( $response = [] ) : array {
        $data = [];

        try {
            $data = (array) json_decode(
                wp_remote_retrieve_body( $response ),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }
        catch ( \JsonException $e ) {
            ( new Logger() )->error( 'process_locations error: ' . print_r( $e, true ) );

            wp_send_json_error( [ 'error' => $e->getMessage() ] );
        }

        $data = $data['data']['unifiedSearch']['edges'] ?? [];

        $data = array_map(
            static fn( $item ) => new API\Entities\Location( $item ),
            $data
        );

        $results = [];
        foreach ( $data as $item ) {
            $id = $item->get_id();

            $results[ $id ] = $item->get_formatted_location();
        }

        return $results;
    }

    /**
     * Get search meta info and events as key-value pairs (id, title).
     *
     * @param array $params Array of query params.
     *
     * @return array
     */
    public static function get_selection_result( array $params ) : array {
        $results = [];

        try {
            $response = self::do_query( $params['search'] ?? '' );
            $results  = self::process_locations( $response );
            $results = SportsLocationsPlugin::locations_selected_callback_output( $results );
        }
        catch ( \JsonException $e ) {
            ( new Logger() )->error( 'get_selection_result: ' . $e->getMessage() );
        }

        return $results;
    }

}
