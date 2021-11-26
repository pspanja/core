<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Event;

use Ibexa\Contracts\Core\Repository\ContentTypeService as ContentTypeServiceInterface;
use Ibexa\Contracts\Core\Repository\Decorator\ContentTypeServiceDecorator;
use Ibexa\Contracts\Core\Repository\Events\ContentType\AddFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\AssignContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeAddFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeAssignContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeCopyContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeCreateContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeCreateContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeCreateContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeDeleteContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeDeleteContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforePublishContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeRemoveContentTypeTranslationEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeRemoveFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeUnassignContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeUpdateContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeUpdateContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeUpdateFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\CopyContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\CreateContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\CreateContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\CreateContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\DeleteContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\DeleteContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\PublishContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\RemoveContentTypeTranslationEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\RemoveFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\UnassignContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\UpdateContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\UpdateContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\UpdateFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeDraft;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroupCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ContentTypeService extends ContentTypeServiceDecorator
{
    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(
        ContentTypeServiceInterface $innerService,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($innerService);

        $this->eventDispatcher = $eventDispatcher;
    }

    public function createContentTypeGroup(ContentTypeGroupCreateStruct $contentTypeGroupCreateStruct): ContentTypeGroup
    {
        $eventData = [$contentTypeGroupCreateStruct];

        $beforeEvent = new BeforeCreateContentTypeGroupEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return $beforeEvent->getContentTypeGroup();
        }

        $contentTypeGroup = $beforeEvent->hasContentTypeGroup()
            ? $beforeEvent->getContentTypeGroup()
            : $this->innerService->createContentTypeGroup($contentTypeGroupCreateStruct);

        $this->eventDispatcher->dispatch(
            new CreateContentTypeGroupEvent($contentTypeGroup, ...$eventData)
        );

        return $contentTypeGroup;
    }

    public function updateContentTypeGroup(
        ContentTypeGroup $contentTypeGroup,
        ContentTypeGroupUpdateStruct $contentTypeGroupUpdateStruct
    ): void {
        $eventData = [
            $contentTypeGroup,
            $contentTypeGroupUpdateStruct,
        ];

        $beforeEvent = new BeforeUpdateContentTypeGroupEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->updateContentTypeGroup($contentTypeGroup, $contentTypeGroupUpdateStruct);

        $this->eventDispatcher->dispatch(
            new UpdateContentTypeGroupEvent(...$eventData)
        );
    }

    public function deleteContentTypeGroup(ContentTypeGroup $contentTypeGroup): void
    {
        $eventData = [$contentTypeGroup];

        $beforeEvent = new BeforeDeleteContentTypeGroupEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->deleteContentTypeGroup($contentTypeGroup);

        $this->eventDispatcher->dispatch(
            new DeleteContentTypeGroupEvent(...$eventData)
        );
    }

    public function createContentType(
        ContentTypeCreateStruct $contentTypeCreateStruct,
        array $contentTypeGroups
    ): ContentTypeDraft {
        $eventData = [
            $contentTypeCreateStruct,
            $contentTypeGroups,
        ];

        $beforeEvent = new BeforeCreateContentTypeEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return $beforeEvent->getContentTypeDraft();
        }

        $contentTypeDraft = $beforeEvent->hasContentTypeDraft()
            ? $beforeEvent->getContentTypeDraft()
            : $this->innerService->createContentType($contentTypeCreateStruct, $contentTypeGroups);

        $this->eventDispatcher->dispatch(
            new CreateContentTypeEvent($contentTypeDraft, ...$eventData)
        );

        return $contentTypeDraft;
    }

    public function createContentTypeDraft(ContentType $contentType): ContentTypeDraft
    {
        $eventData = [$contentType];

        $beforeEvent = new BeforeCreateContentTypeDraftEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return $beforeEvent->getContentTypeDraft();
        }

        $contentTypeDraft = $beforeEvent->hasContentTypeDraft()
            ? $beforeEvent->getContentTypeDraft()
            : $this->innerService->createContentTypeDraft($contentType);

        $this->eventDispatcher->dispatch(
            new CreateContentTypeDraftEvent($contentTypeDraft, ...$eventData)
        );

        return $contentTypeDraft;
    }

    public function updateContentTypeDraft(
        ContentTypeDraft $contentTypeDraft,
        ContentTypeUpdateStruct $contentTypeUpdateStruct
    ): void {
        $eventData = [
            $contentTypeDraft,
            $contentTypeUpdateStruct,
        ];

        $beforeEvent = new BeforeUpdateContentTypeDraftEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->updateContentTypeDraft($contentTypeDraft, $contentTypeUpdateStruct);

        $this->eventDispatcher->dispatch(
            new UpdateContentTypeDraftEvent(...$eventData)
        );
    }

    public function deleteContentType(ContentType $contentType): void
    {
        $eventData = [$contentType];

        $beforeEvent = new BeforeDeleteContentTypeEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->deleteContentType($contentType);

        $this->eventDispatcher->dispatch(
            new DeleteContentTypeEvent(...$eventData)
        );
    }

    public function copyContentType(
        ContentType $contentType,
        User $creator = null
    ): ContentType {
        $eventData = [
            $contentType,
            $creator,
        ];

        $beforeEvent = new BeforeCopyContentTypeEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return $beforeEvent->getContentTypeCopy();
        }

        $contentTypeCopy = $beforeEvent->hasContentTypeCopy()
            ? $beforeEvent->getContentTypeCopy()
            : $this->innerService->copyContentType($contentType, $creator);

        $this->eventDispatcher->dispatch(
            new CopyContentTypeEvent($contentTypeCopy, ...$eventData)
        );

        return $contentTypeCopy;
    }

    public function assignContentTypeGroup(
        ContentType $contentType,
        ContentTypeGroup $contentTypeGroup
    ): void {
        $eventData = [
            $contentType,
            $contentTypeGroup,
        ];

        $beforeEvent = new BeforeAssignContentTypeGroupEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->assignContentTypeGroup($contentType, $contentTypeGroup);

        $this->eventDispatcher->dispatch(
            new AssignContentTypeGroupEvent(...$eventData)
        );
    }

    public function unassignContentTypeGroup(
        ContentType $contentType,
        ContentTypeGroup $contentTypeGroup
    ): void {
        $eventData = [
            $contentType,
            $contentTypeGroup,
        ];

        $beforeEvent = new BeforeUnassignContentTypeGroupEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->unassignContentTypeGroup($contentType, $contentTypeGroup);

        $this->eventDispatcher->dispatch(
            new UnassignContentTypeGroupEvent(...$eventData)
        );
    }

    public function addFieldDefinition(
        ContentTypeDraft $contentTypeDraft,
        FieldDefinitionCreateStruct $fieldDefinitionCreateStruct
    ): void {
        $eventData = [
            $contentTypeDraft,
            $fieldDefinitionCreateStruct,
        ];

        $beforeEvent = new BeforeAddFieldDefinitionEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->addFieldDefinition($contentTypeDraft, $fieldDefinitionCreateStruct);

        $this->eventDispatcher->dispatch(
            new AddFieldDefinitionEvent(...$eventData)
        );
    }

    public function removeFieldDefinition(
        ContentTypeDraft $contentTypeDraft,
        FieldDefinition $fieldDefinition
    ): void {
        $eventData = [
            $contentTypeDraft,
            $fieldDefinition,
        ];

        $beforeEvent = new BeforeRemoveFieldDefinitionEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->removeFieldDefinition($contentTypeDraft, $fieldDefinition);

        $this->eventDispatcher->dispatch(
            new RemoveFieldDefinitionEvent(...$eventData)
        );
    }

    public function updateFieldDefinition(
        ContentTypeDraft $contentTypeDraft,
        FieldDefinition $fieldDefinition,
        FieldDefinitionUpdateStruct $fieldDefinitionUpdateStruct
    ): void {
        $eventData = [
            $contentTypeDraft,
            $fieldDefinition,
            $fieldDefinitionUpdateStruct,
        ];

        $beforeEvent = new BeforeUpdateFieldDefinitionEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->updateFieldDefinition($contentTypeDraft, $fieldDefinition, $fieldDefinitionUpdateStruct);

        $this->eventDispatcher->dispatch(
            new UpdateFieldDefinitionEvent(...$eventData)
        );
    }

    public function publishContentTypeDraft(ContentTypeDraft $contentTypeDraft): void
    {
        $eventData = [$contentTypeDraft];

        $beforeEvent = new BeforePublishContentTypeDraftEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return;
        }

        $this->innerService->publishContentTypeDraft($contentTypeDraft);

        $this->eventDispatcher->dispatch(
            new PublishContentTypeDraftEvent(...$eventData)
        );
    }

    public function removeContentTypeTranslation(
        ContentTypeDraft $contentTypeDraft,
        string $languageCode
    ): ContentTypeDraft {
        $eventData = [
            $contentTypeDraft,
            $languageCode,
        ];

        $beforeEvent = new BeforeRemoveContentTypeTranslationEvent(...$eventData);

        $this->eventDispatcher->dispatch($beforeEvent);
        if ($beforeEvent->isPropagationStopped()) {
            return $beforeEvent->getNewContentTypeDraft();
        }

        $newContentTypeDraft = $beforeEvent->hasNewContentTypeDraft()
            ? $beforeEvent->getNewContentTypeDraft()
            : $this->innerService->removeContentTypeTranslation($contentTypeDraft, $languageCode);

        $this->eventDispatcher->dispatch(
            new RemoveContentTypeTranslationEvent($newContentTypeDraft, ...$eventData)
        );

        return $newContentTypeDraft;
    }
}

class_alias(ContentTypeService::class, 'eZ\Publish\Core\Event\ContentTypeService');
