<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event emitted after action execution.
 */
abstract class AfterEvent extends Event
{
}

class_alias(AfterEvent::class, 'eZ\Publish\SPI\Repository\Event\AfterEvent');
