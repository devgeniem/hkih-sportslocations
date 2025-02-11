<?php
/**
 * Location entity
 */

namespace HKIH\SportsLocations\API\Entities;

use Geniem\Theme\Localization;

/**
 * Class Location
 *
 * @package HKIH\SportsLocations\API\Entities
 */
class Location {

    /**
     * Entity data
     *
     * @var mixed
     */
    protected $entity_data;
    /**
     * Location ID.
     *
     * @var int
     */
    public int $id = 0;
    /**
     * Location names.
     *
     * @var array
     */
    public object $name;

    /**
     * Entity constructor.
     *
     * @param mixed $entity_data Entity data.
     */
    public function __construct( $entity_data ) {
        $this->entity_data = $entity_data['node']['venue'] ?? [];

        $this->id   = (int) ( $this->entity_data['meta']['id'] ?? null );
        $this->name = (object) ( $this->entity_data['name'] ?? [] );
    }

    /**
     * Get Formatted Location.
     *
     * @param string|null $lang Language.
     *
     * @return array
     */
    public function get_formatted_location( string $lang = null ) : array {
        $formatted_name = sprintf(
            '%s (id: %s)',
            $this->get_key_values_by_language( 'name', $lang ),
            $this->id,
        );

        return [
            'id'   => $this->id,
            'text' => $formatted_name,
        ];
    }

    /**
     * Get Id
     *
     * @return mixed
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return string|null
     */
    public function get_name() : ?string {
        return $this->get_key_by_language( 'name' );
    }

    /**
     * @param string      $key  Key to get data off.
     * @param string|null $lang Language to use to access the data.
     *
     * @return null|string
     */
    public function get_key_values_by_language( string $key, string $lang = null ) : ?string {
        if ( empty( $lang ) ) {
            return $this->get_key_by_language( $key );
        }

        return $this->{$key}->{$lang} ?? null;
    }

    /**
     * Get key values
     *
     * @param string $key Entity object key.
     *
     * @return string|null
     */
    public function get_key_values( string $key ) : ?string {
        return $this->{$key} ?? null;
    }

    /**
     * Get key by language
     *
     * @param string      $key         Event object key.
     * @param bool|object $entity_data Entity data.
     *
     * @return string|null
     */
    protected function get_key_by_language( string $key, $entity_data = false ) : ?string {
        $current_language = Localization::get_current_language();
        $default_language = Localization::get_default_language();

        if ( ! $entity_data ) {
            $entity_data = $this->entity_data;
        }

        if ( isset( $this->{$key} ) ) {
            if ( isset( $this->{$key}->{$current_language} ) ) {
                return $this->get_key_values_by_language( $key, $current_language );
            }

            if ( isset( $this->{$key}->{$default_language} ) ) {
                return $this->get_key_values_by_language( $key, $default_language );
            }
        }

        if ( isset( $entity_data->{$key} ) ) {
            if ( isset( $entity_data->{$key}->{$current_language} ) ) {
                return $entity_data->{$key}->{$current_language};
            }

            if ( isset( $entity_data->{$key}->{$default_language} ) ) {
                return $entity_data->{$key}->{$default_language};
            }
        }

        return null;
    }
}
