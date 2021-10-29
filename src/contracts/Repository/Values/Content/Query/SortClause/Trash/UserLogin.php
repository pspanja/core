<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Trash;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Trash\Query\SortClause as TrashSortClause;

class UserLogin extends SortClause implements TrashSortClause
{
    public function __construct(string $sortDirection = Query::SORT_ASC)
    {
        parent::__construct('user_name', $sortDirection);
    }
}

class_alias(UserLogin::class, 'eZ\Publish\API\Repository\Values\Content\Query\SortClause\Trash\UserLogin');
