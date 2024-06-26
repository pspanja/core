<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Repository\Values\ContentType;

use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup as APIContentTypeGroup;
use Ibexa\Core\Repository\Values\MultiLanguageDescriptionTrait;
use Ibexa\Core\Repository\Values\MultiLanguageNameTrait;
use Ibexa\Core\Repository\Values\MultiLanguageTrait;

/**
 * This class represents a content type group value.
 *
 * @property-read string[] $names calls getNames() or on access getName($language)
 * @property-read string[] $descriptions calls getDescriptions() or on access getDescription($language)
 * @property-read mixed $id the id of the content type group
 * @property-read string $identifier the identifier of the content type group
 * @property-read \DateTime $creationDate the date of the creation of this content type group
 * @property-read \DateTime $modificationDate the date of the last modification of this content type group
 * @property-read mixed $creatorId the user id of the creator of this content type group
 * @property-read mixed $modifierId the user id of the user which has last modified this content type group
 * @property-read string $mainLanguageCode 5.0, the main language of the content type group names and description used for fallback.
 *
 * @internal Meant for internal use by Repository, type hint against API object instead.
 */
class ContentTypeGroup extends APIContentTypeGroup
{
    use MultiLanguageTrait;
    use MultiLanguageNameTrait;
    use MultiLanguageDescriptionTrait;
}

class_alias(ContentTypeGroup::class, 'eZ\Publish\Core\Repository\Values\ContentType\ContentTypeGroup');
