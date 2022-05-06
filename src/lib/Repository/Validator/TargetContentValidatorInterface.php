<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Validator;

use Ibexa\Contracts\Core\FieldType\ValidationError;

/**
 * @internal
 */
interface TargetContentValidatorInterface
{
    public function validate(int $value, array $allowedContentTypes = []): ?ValidationError;
}
