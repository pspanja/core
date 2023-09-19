<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\NameSchema;

interface SchemaIdentifierExtractorInterface
{
    /**
     * @return array<string, array<int, string>>
     */
    public function extract(string $schemaString): array;
}
