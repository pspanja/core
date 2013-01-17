<?php
/**
 * File containing the FieldTypeRegistry class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\Persistence;

use eZ\Publish\SPI\FieldType\FieldType as FieldTypeInterface;
use eZ\Publish\Core\Persistence\FieldType;
use RuntimeException;

/**
 * Registry for field types available to storage engines.
 */
class FieldTypeRegistry
{
    /**
     * @var array Hash of SPI FieldTypes or callable callbacks to generate one.
     */
    protected $settings = array();

    /**
     * Map of FieldTypes.
     *
     * @var \eZ\Publish\Core\Persistence\FieldType[]
     */
    protected $fieldTypeMap = array();

    /**
     * Creates FieldType registry.
     *
     * In $settings a mapping of field type identifier to object / callable is
     * expected, in case of callable factory it should return the FieldType object.
     * The FieldType object must comply to the {@link \eZ\Publish\SPI\Persistence\FieldType} interface.
     *
     * @param array $settings A map where key is field type identifier and value is
     *              a callable factory to get FieldType OR FieldType object.
     */
    public function __construct( array $settings )
    {
        $this->settings = $settings;
    }

    /**
     * Returns the FieldType object for given $identifier.
     *
     * @param string $identifier
     *
     * @throws \RuntimeException If field type for given $identifier is not found.
     * @throws \RuntimeException If field type for given $identifier is not instance or callable.
     *
     * @return \eZ\Publish\SPI\Persistence\FieldType
     */
    public function getFieldType( $identifier )
    {
        if ( isset( $this->fieldTypeMap[$identifier] ) )
        {
            return $this->fieldTypeMap[$identifier];
        }

        $this->fieldTypeMap[$identifier] = new FieldType( $this->buildFieldType( $identifier ) );

        return $this->fieldTypeMap[$identifier];
    }

    /**
     * Instantiates a FieldType object.
     *
     * @throws \RuntimeException If field type for given $identifier is not found.
     * @throws \RuntimeException If field type for given $identifier is not instance or callable.
     *
     * @param string $identifier
     *
     * @return \eZ\Publish\SPI\FieldType\FieldType
     */
    protected function buildFieldType( $identifier )
    {
        if ( !isset( $this->settings[$identifier] ) )
        {
            throw new RuntimeException(
                "Provided \$identifier is unknown: '{$identifier}', have: "
                . var_export( array_keys( $this->settings ), true )
            );
        }

        if ( !$this->settings[$identifier] instanceof FieldTypeInterface
            && !is_callable( $this->settings[$identifier] )
        )
        {
            throw new RuntimeException( "FieldType '$identifier' is not callable or instance" );
        }

        /** @var $closure \Closure */
        $closure = $this->settings[$identifier];
        return $closure();
    }
}
