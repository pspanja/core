<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;

final class BeforeMoveUserGroupEvent extends BeforeEvent
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup */
    private $userGroup;

    /** @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup */
    private $newParent;

    public function __construct(UserGroup $userGroup, UserGroup $newParent)
    {
        $this->userGroup = $userGroup;
        $this->newParent = $newParent;
    }

    public function getUserGroup(): UserGroup
    {
        return $this->userGroup;
    }

    public function getNewParent(): UserGroup
    {
        return $this->newParent;
    }
}

class_alias(BeforeMoveUserGroupEvent::class, 'eZ\Publish\API\Repository\Events\User\BeforeMoveUserGroupEvent');
