<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Role;

use Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Event\AfterEvent;

final class AssignRoleToUserGroupEvent extends AfterEvent
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\User\Role */
    private $role;

    /** @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup */
    private $userGroup;

    /** @var \Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation */
    private $roleLimitation;

    public function __construct(
        Role $role,
        UserGroup $userGroup,
        ?RoleLimitation $roleLimitation = null
    ) {
        $this->role = $role;
        $this->userGroup = $userGroup;
        $this->roleLimitation = $roleLimitation;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getUserGroup(): UserGroup
    {
        return $this->userGroup;
    }

    public function getRoleLimitation(): ?RoleLimitation
    {
        return $this->roleLimitation;
    }
}

class_alias(AssignRoleToUserGroupEvent::class, 'eZ\Publish\API\Repository\Events\Role\AssignRoleToUserGroupEvent');
