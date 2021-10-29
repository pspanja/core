<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use UnexpectedValueException;

final class BeforeCopyContentEvent extends BeforeEvent
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo */
    private $contentInfo;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct */
    private $destinationLocationCreateStruct;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo */
    private $versionInfo;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content|null */
    private $content;

    public function __construct(
        ContentInfo $contentInfo,
        LocationCreateStruct $destinationLocationCreateStruct,
        ?VersionInfo $versionInfo = null
    ) {
        $this->contentInfo = $contentInfo;
        $this->destinationLocationCreateStruct = $destinationLocationCreateStruct;
        $this->versionInfo = $versionInfo;
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->contentInfo;
    }

    public function getDestinationLocationCreateStruct(): LocationCreateStruct
    {
        return $this->destinationLocationCreateStruct;
    }

    public function getVersionInfo(): ?VersionInfo
    {
        return $this->versionInfo;
    }

    public function getContent(): Content
    {
        if (!$this->hasContent()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not a type of %s. Check hasContent() or set it using setContent() before you call the getter.', Content::class));
        }

        return $this->content;
    }

    public function setContent(?Content $content): void
    {
        $this->content = $content;
    }

    public function hasContent(): bool
    {
        return $this->content instanceof Content;
    }
}

class_alias(BeforeCopyContentEvent::class, 'eZ\Publish\API\Repository\Events\Content\BeforeCopyContentEvent');
