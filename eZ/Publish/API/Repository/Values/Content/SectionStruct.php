<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\API\Repository\Values\Content;

use eZ\Publish\API\Repository\Values\ValueObject;

abstract class SectionStruct extends ValueObject
{
    /**
     * If set the Unique identifier of the section is changes.
     *
     * Needs to be a unique Section->identifier string value.
     *
     * @var string
     */
    public $identifier;

    /**
     * If set the name of the section is changed.
     *
     * @var string
     */
    public $name;
}
