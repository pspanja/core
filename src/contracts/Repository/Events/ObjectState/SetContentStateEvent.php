<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\ObjectState;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup;
use Ibexa\Contracts\Core\Repository\Event\AfterEvent;

final class SetContentStateEvent extends AfterEvent
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo */
    private $contentInfo;

    /** @var \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup */
    private $objectStateGroup;

    /** @var \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState */
    private $objectState;

    public function __construct(
        ContentInfo $contentInfo,
        ObjectStateGroup $objectStateGroup,
        ObjectState $objectState
    ) {
        $this->contentInfo = $contentInfo;
        $this->objectStateGroup = $objectStateGroup;
        $this->objectState = $objectState;
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->contentInfo;
    }

    public function getObjectStateGroup(): ObjectStateGroup
    {
        return $this->objectStateGroup;
    }

    public function getObjectState(): ObjectState
    {
        return $this->objectState;
    }
}

class_alias(SetContentStateEvent::class, 'eZ\Publish\API\Repository\Events\ObjectState\SetContentStateEvent');
