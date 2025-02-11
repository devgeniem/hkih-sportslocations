<?php
/**
 * Custom Relationship field for Advanced Custom Fields
 */

use Geniem\Theme\Logger;
use HKIH\SportsLocations\SportsLocationsPlugin;

/**
 * Class AcfLocationFieldRestRelationship
 */
class AcfLocationFieldRestRelationship extends acf_field_relationship {

    /**
     *  __construct
     *
     *  This function will setup the field type data
     */
    public function initialize() : void {
        // vars
        $this->name     = 'rest_relationship_locations';
        $this->label    = __( 'REST Relationship', 'acf' );
        $this->category = 'choice';
        $this->defaults = [
            'placeholder'   => '',
            'return_format' => 'value',
        ];

        // extra
        add_action(
            'wp_ajax_acf/fields/' . $this->name . '/query',
            [ $this, 'ajax_query' ]
        );

        add_action(
            'wp_ajax_nopriv_acf/fields/' . $this->name . '/query',
            [ $this, 'ajax_query' ]
        );
    }

    /**
     *  render_field()
     *
     *  Create the HTML interface for your field
     *
     * @param array $field An array holding all the field's data
     */
    public function render_field( $field = [] ) : void {
        // div attributes
        $attributes = [
            'id'          => $field['id'],
            'class'       => "acf-rest-relationship-location acf-relationship {$field['class']}",
            'data-min'    => $field['min'] ?? 0,
            'data-max'    => $field['max'] ?? 100,
            'data-query'  => $field['query'] ?? '',
            'data-search' => $field['search'] ?? '',
        ];

        echo '<div ' . acf_esc_attrs( $attributes ) . '>';

        acf_hidden_input( [ 'name' => $field['name'], 'value' => '' ] );
        ?>

        <div class="selection rest_relationship_locations relationship_selection">
            <div class="choices">
                <ul class="acf-bl list choices-list"></ul>
            </div>
            <div class="values">
                <ul class="acf-bl list values-list">
                    <?php
                    if ( ! empty( $field['value'] ) ) {
                        $posts = $field['value'];

                        foreach ( $posts as $key => $title ) {
                            echo $this->generate_item( $field['name'], $key, $title );
                        }
                    } ?>
                </ul>
            </div>
        </div>

        <?php
        echo '</div>';
    }

    /**
     * Generate ACF Relationship type list item.
     *
     * @param string $field_name ACF Field name.
     * @param string $key        ID.
     * @param string $title      Item title.
     *
     * @return string
     */
    private function generate_item( $field_name = '', $key = '', $title = '' ) : string {
        $hiddenFields   = acf_get_hidden_input( [
            'name'  => $field_name . '[' . $key . ']',
            'value' => $title,
        ] );
        $spanAttributes = sprintf( 'data-id="%s" class="acf-rel-item"', $key ?? '' );
        $removalLink    = '<a href="#" class="acf-icon -minus small dark" data-name="remove_item"></a>';

        $title = sprintf(
            '<span %s>%s %s</span>',
            $spanAttributes,
            acf_esc_html( $title ),
            $removalLink
        );

        return '<li>' . $hiddenFields . $title . '</li>';
    }

    /**
     * AJAX Query.
     */
    public function ajax_query() : void {
        // validate
        if ( ! acf_verify_ajax() ) {
            acf_send_ajax_results( [ 'error' => ':(' ] );
            die();
        }

        $response = $this->get_rest_ajax_query( $_POST );
        acf_send_ajax_results( $response );
    }

    /**
     * Get Results with AJAX.
     *
     * @param array $options Options passed from ACF.
     *
     * @return array
     */
    public function get_rest_ajax_query( array $options = [] ) : array {
        // defaults
        $options = (array) wp_parse_args( $options, [
            'post_id'   => 0,
            'field_key' => '',
            'query'     => '',
            'search'    => '',
        ] );

        // load field
        $field = acf_get_field( $options['field_key'] );
        if ( ! $field ) {
            return [];
        }

        // vars
        $args = [];

        if ( isset( $options['query'] ) && ! empty( $options['query'] ) ) {
            $query = array_filter( $options['query'] );
            foreach ( $query as $query_key => $query_val ) {
                $args[ $query_key ] = $query_val;
            }
        }

        // filters
        $args = apply_filters(
            'acf/fields/' . $this->name . '/query',
            $args, $field, $options['post_id']
        );
        $args = apply_filters(
            'acf/fields/' . $this->name . '/query/name=' . $field['name'],
            $args, $field, $options['post_id']
        );
        $args = apply_filters(
            'acf/fields/' . $this->name . '/query/key=' . $field['key'],
            $args, $field, $options['post_id']
        );

        // We now should have all the data from 'acf/fields/rest_relationship_locations/query' filter.
        $data = [];
        try {
            $data = SportsLocationsPlugin::locations_selected_fetch( $args );
            $data = SportsLocationsPlugin::locations_selected_callback_output( $data );
        }
        catch ( JsonException $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTrace() );
        }

        // filters
        $data = apply_filters(
            'acf/fields/' . $this->name . '/post_rest_call',
            $data, $field, $options['post_id']
        );

        return $data;
    }
}

acf_register_field_type( AcfLocationFieldRestRelationship::class );
