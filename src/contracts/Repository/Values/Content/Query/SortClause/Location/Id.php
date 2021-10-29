<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Location;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;

/**
 * Sets sort direction on Location id for a Location query.
 *
 * Especially useful to get reproducible search results in tests.
 */
class Id extends Location implements FilteringSortClause
{
    /**
     * Constructs a new LocationId SortClause.
     *
     * @param string $sortDirection
     */
    public function __construct(string $sortDirection = Query::SORT_ASC)
    {
        parent::__construct('location_id', $sortDirection);
    }
}

class_alias(Id::class, 'eZ\Publish\API\Repository\Values\Content\Query\SortClause\Location\Id');
