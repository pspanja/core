<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

class IsUserBased extends CriterionHandler
{
    public function accept(Criterion $criterion): bool
    {
        return $criterion instanceof Criterion\IsUserBased;
    }

    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        Criterion $criterion,
        array $languageSettings
    ) {
        $isUserBased = (bool)reset($criterion->value);

        $subSelect = $this->connection->createQueryBuilder();
        $subSelect
            ->select(
                'contentobject_id'
            )->from(
                'ezuser'
            );

        $queryExpression = $queryBuilder->expr()->in(
            'c.id',
            $subSelect->getSQL()
        );

        return $isUserBased
            ? $queryExpression
            : "NOT({$queryExpression})";
    }
}
