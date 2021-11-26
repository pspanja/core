<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway;

use Doctrine\DBAL\DBALException;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway;
use PDOException;

/**
 * @internal Internal exception conversion layer.
 */
final class ExceptionConversion extends Gateway
{
    /**
     * The wrapped gateway.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway
     */
    private $innerGateway;

    /**
     * Creates a new exception conversion gateway around $innerGateway.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway $innerGateway
     */
    public function __construct(Gateway $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function setTable(string $name): void
    {
        try {
            $this->innerGateway->setTable($name);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadAllLocationEntries(int $locationId): array
    {
        try {
            return $this->innerGateway->loadAllLocationEntries($locationId);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadLocationEntries(
        int $locationId,
        bool $custom = false,
        ?int $languageId = null
    ): array {
        try {
            return $this->innerGateway->loadLocationEntries($locationId, $custom);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function isRootEntry(int $id): bool
    {
        try {
            return $this->innerGateway->isRootEntry($id);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function cleanupAfterPublish(
        string $action,
        int $languageId,
        int $newId,
        int $parentId,
        string $textMD5
    ): void {
        try {
            $this->innerGateway->cleanupAfterPublish($action, $languageId, $newId, $parentId, $textMD5);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function historizeBeforeSwap(string $action, int $languageMask): void
    {
        try {
            $this->innerGateway->historizeBeforeSwap($action, $languageMask);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function historizeId(int $id, int $link): void
    {
        try {
            $this->innerGateway->historizeId($id, $link);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function reparent(int $oldParentId, int $newParentId): void
    {
        try {
            $this->innerGateway->reparent($oldParentId, $newParentId);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function updateRow(int $parentId, string $textMD5, array $values): void
    {
        try {
            $this->innerGateway->updateRow($parentId, $textMD5, $values);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function insertRow(array $values): int
    {
        try {
            return $this->innerGateway->insertRow($values);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadRow(int $parentId, string $textMD5): array
    {
        try {
            return $this->innerGateway->loadRow($parentId, $textMD5);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadAutogeneratedEntry(string $action, ?int $parentId = null): array
    {
        try {
            return $this->innerGateway->loadAutogeneratedEntry($action, $parentId);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function remove(string $action, ?int $id = null): void
    {
        try {
            $this->innerGateway->remove($action, $id);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function listGlobalEntries(
        ?string $languageCode = null,
        int $offset = 0,
        int $limit = -1
    ): array {
        try {
            return $this->innerGateway->listGlobalEntries($languageCode, $offset, $limit);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function removeCustomAlias(int $parentId, string $textMD5): bool
    {
        try {
            return $this->innerGateway->removeCustomAlias($parentId, $textMD5);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadUrlAliasData(array $urlHashes): array
    {
        try {
            return $this->innerGateway->loadUrlAliasData($urlHashes);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadPathData(int $id): array
    {
        try {
            return $this->innerGateway->loadPathData($id);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadPathDataByHierarchy(array $hierarchyData): array
    {
        try {
            return $this->innerGateway->loadPathDataByHierarchy($hierarchyData);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadAutogeneratedEntries(int $parentId, bool $includeHistory = false): array
    {
        try {
            return $this->innerGateway->loadAutogeneratedEntries($parentId, $includeHistory);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getNextId(): int
    {
        try {
            return $this->innerGateway->getNextId();
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getLocationContentMainLanguageId(int $locationId): int
    {
        try {
            return $this->innerGateway->getLocationContentMainLanguageId($locationId);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function bulkRemoveTranslation(int $languageId, array $actions): void
    {
        try {
            $this->innerGateway->bulkRemoveTranslation($languageId, $actions);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function archiveUrlAliasesForDeletedTranslations(
        int $locationId,
        int $parentId,
        array $languageIds
    ): void {
        try {
            $this->innerGateway->archiveUrlAliasesForDeletedTranslations($locationId, $parentId, $languageIds);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteUrlAliasesWithoutLocation(): int
    {
        try {
            return $this->innerGateway->deleteUrlAliasesWithoutLocation();
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteUrlAliasesWithoutParent(): int
    {
        try {
            return $this->innerGateway->deleteUrlAliasesWithoutParent();
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteUrlAliasesWithBrokenLink(): int
    {
        try {
            return $this->innerGateway->deleteUrlAliasesWithBrokenLink();
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function repairBrokenUrlAliasesForLocation(int $locationId): void
    {
        try {
            $this->innerGateway->repairBrokenUrlAliasesForLocation($locationId);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteUrlNopAliasesWithoutChildren(): int
    {
        try {
            return $this->innerGateway->deleteUrlNopAliasesWithoutChildren();
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getAllChildrenAliases(int $parentId): array
    {
        try {
            return $this->innerGateway->getAllChildrenAliases($parentId);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}

class_alias(ExceptionConversion::class, 'eZ\Publish\Core\Persistence\Legacy\Content\UrlAlias\Gateway\ExceptionConversion');
