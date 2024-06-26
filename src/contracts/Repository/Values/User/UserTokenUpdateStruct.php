<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class is used to update a user token in the repository.
 */
class UserTokenUpdateStruct extends ValueObject
{
    /**
     * Hash key date for user account.
     *
     * @var string
     */
    public $hashKey;

    /**
     * Time to which the token is valid.
     *
     * @var \DateTime|null
     */
    public $time;
}

class_alias(UserTokenUpdateStruct::class, 'eZ\Publish\API\Repository\Values\User\UserTokenUpdateStruct');
