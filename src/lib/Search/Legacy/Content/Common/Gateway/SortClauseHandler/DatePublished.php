<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler;

/**
 * Content locator gateway implementation using the DoctrineDatabase.
 */
class DatePublished extends SortClauseHandler
{
    /**
     * Check if this sort clause handler accepts to handle the given sort clause.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause $sortClause
     *
     * @return bool
     */
    public function accept(SortClause $sortClause)
    {
        return $sortClause instanceof SortClause\DatePublished;
    }

    public function applySelect(
        QueryBuilder $query,
        SortClause $sortClause,
        int $number
    ): array {
        $query
            ->addSelect(
                sprintf(
                    'c.published AS %s',
                    $column = $this->getSortColumnName($number)
                )
            );

        return [$column];
    }
}

class_alias(DatePublished::class, 'eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\DatePublished');
