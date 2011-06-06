<?php
/**
 * Content Service, extends repository with content specific operations
 *
 * @copyright Copyright (c) 2011, eZ Systems AS
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2.0
 * @package ext
 * @subpackage doctrine
 */

/**
 * Content Service, extends repository with content specific operations
 */
namespace ezx\doctrine\model;
class ContentService implements Interface_Service
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * Setups current instance with reference to repository object that created it.
     *
     * @param Repository $repository
     */
    public function __construct( Interface_Repository $repository )
    {
        $this->repository = $repository;
    }

    /**
     * Get an Content object by id
     *
     * @param int $id
     * @return Content
     * @throws \InvalidArgumentException
     */
    public function load( $id )
    {
        $content = $this->repository->em->find( "ezx\doctrine\model\Content", (int) $id );
        if ( !$content )
            throw new \InvalidArgumentException( "Could not find 'Content' with id: {$id}" );
        return $content;
    }

    /**
     * Create content object
     *
     * @uses ContentTypeService::loadByIdentifier()
     * @param string $typeIdentifier
     * @return Content
     */
    public function create( $typeIdentifier )
    {
        $type = $this->repository->ContentTypeService()->loadByIdentifier( $typeIdentifier );
        if ( !$type )
            throw new \RuntimeException( "Could not find content type by identifier: '{$typeIdentifier}'" );

        return new Content( $type );
    }
}
