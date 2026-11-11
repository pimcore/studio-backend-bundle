<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration;

use Closure;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\IdentifierOrderedChunkTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing;
use Throwable;
use function array_unique;
use function array_values;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function sprintf;
use function strtoupper;

/**
 * @internal
 */
final readonly class KeyRepository implements KeyRepositoryInterface
{
    use IdentifierOrderedChunkTrait;

    private const array ALLOWED_SORT_KEYS = [
        'name',
        'title',
        'description',
        'id',
        'type',
        'creationDate',
        'modificationDate',
        'enabled',
        'storeId',
    ];

    private const array ALLOWED_SORT_DIRECTIONS = ['ASC', 'DESC'];

    /**
     * Own parameter name, so a client supplied equals filter on `storeId` narrows the route's store
     * binding instead of replacing the condition Pimcore keys by its own string.
     */
    private const string STORE_SCOPE_PARAMETER = 'scopeStoreId';

    private const string STORE_SCOPE_CONDITION = '`storeId` = :' . self::STORE_SCOPE_PARAMETER;

    private const string IDENTIFIER_QUERY =
        'SELECT `id`, `identifier` FROM `%s` WHERE `storeId` = ? AND `identifier` IN (?)';

    private const string ID_QUERY =
        'SELECT `id`, `identifier` FROM `%s` WHERE `storeId` = ? AND `id` IN (?) AND `identifier` IS NOT NULL';

    public function __construct(
        private KeyConfigResolverInterface $keyConfigResolver,
        private KeyLocalizationResolverInterface $keyLocalizationResolver,
        private ListingFilterInterface $listingFilter,
        private SearchHelperServiceInterface $searchHelperService,
        private Connection $connection,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getListing(FilterParameter $parameters, int $storeId, bool $disabledOnly = false): Listing
    {
        $listing = new Listing();
        // core's `includeDisabled` swaps the enabled condition for the disabled one, it never unions them
        $listing->setIncludeDisabled($disabledOnly);
        $listing->addConditionParam(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);

        $this->applySearchCondition($listing, $parameters);
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): KeyConfig
    {
        $config = $this->keyConfigResolver->getById($id);

        if (!$config) {
            throw new NotFoundException('key configuration', $id);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdsByIdentifiers(int $storeId, array $identifiers): array
    {
        $identifiers = array_values(array_unique($identifiers));

        if (empty($identifiers)) {
            return [];
        }

        try {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(self::IDENTIFIER_QUERY, KeyConfigDao::TABLE_NAME_KEYS),
                [$storeId, $identifiers],
                [ParameterType::INTEGER, ArrayParameterType::STRING]
            );
        } catch (DBALException $e) {
            throw new DatabaseException('Failed to read the keys by identifier: ' . $e->getMessage(), $e);
        }

        $resolved = [];
        foreach ($rows as $row) {
            $resolved[(string) $row['identifier']] = (int) $row['id'];
        }

        return $resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifiersByIds(int $storeId, array $ids): array
    {
        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            return [];
        }

        try {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(self::ID_QUERY, KeyConfigDao::TABLE_NAME_KEYS),
                [$storeId, $ids],
                [ParameterType::INTEGER, ArrayParameterType::INTEGER]
            );
        } catch (DBALException $e) {
            throw new DatabaseException('Failed to read the key identifiers: ' . $e->getMessage(), $e);
        }

        $identifiers = [];
        foreach ($rows as $row) {
            $identifiers[(int) $row['id']] = (string) $row['identifier'];
        }

        return $identifiers;
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkAfterIdentifier(
        int $storeId,
        bool $disabledOnly,
        ?string $afterIdentifier,
        int $afterId,
        int $limit,
    ): array {
        $listing = new Listing();
        // the flag core spells `includeDisabled` narrows to the disabled rows rather than adding
        // them, so the two states are two walks and never one
        $listing->setIncludeDisabled($disabledOnly);
        $listing->addConditionParam(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);
        $this->applyIdentifierCursorChunk($listing, $limit, $afterIdentifier, $afterId);

        return $listing->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkAfterId(
        int $storeId,
        bool $disabledOnly,
        int $afterId,
        int $limit,
        ?bool $withIdentifier = null,
    ): array {
        $listing = new Listing();
        $listing->setIncludeDisabled($disabledOnly);
        $listing->addConditionParam(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);
        $listing->addConditionParam('`id` > :afterId', ['afterId' => $afterId]);
        $this->applyIdentifierPhase($listing, $withIdentifier);
        $this->applyIdCursorChunk($listing, $limit);

        return $listing->load();
    }

    /**
     * {@inheritdoc}
     */
    public function saveFromArtifact(?KeyConfig $config, int $storeId, ArtifactKeyEntry $entry): KeyWriteResult
    {
        return $this->writeHoldingTheRow(
            $config?->getId(),
            fn (): KeyConfig => $this->writeArtifactEntry($config, $storeId, $entry)
        );
    }

    /**
     * @throws ElementSavingFailedException
     */
    private function writeArtifactEntry(?KeyConfig $config, int $storeId, ArtifactKeyEntry $entry): KeyConfig
    {
        $config ??= new KeyConfig();
        $config->setStoreId($storeId);
        $config->setName($entry->getName());
        $config->setTitle($entry->getTitle() ?? '');
        $config->setDescription($entry->getDescription());
        $config->setType($entry->getType());
        $config->setEnabled($entry->isEnabled());
        // unconditionally, null included: a file written before the flag existed carries none and
        // restores every key to inheriting, which is the state such a file describes
        $this->keyLocalizationResolver->setLocalized($config, $entry->getLocalized());

        if ($entry->isCustomMetadataSupplied()) {
            $config->setCustomMetadata($entry->getCustomMetadata());
        }

        try {
            $config->setDefinition(json_encode($entry->getDefinition() ?? [], JSON_THROW_ON_ERROR));
            // before the save, so that a new row carries the file's identifier instead of a fresh one
            $config->setIdentifier($entry->getIdentifier());
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($config->getId(), $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, int $storeId, ?string $title = null): KeyConfig
    {
        $title = ($title === null || $title === '') ? $name : $title;
        $definition = [
            'fieldtype' => 'input',
            'name' => $name,
            'title' => $title,
            'datatype' => 'data',
        ];

        $config = new KeyConfig();
        $config->setName($name);
        $config->setTitle($title);
        $config->setType('input');
        $config->setStoreId($storeId);
        $config->setEnabled(true);

        try {
            $config->setDefinition(json_encode($definition, JSON_THROW_ON_ERROR));
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, KeyUpdate $parameters): KeyWriteResult
    {
        return $this->writeHoldingTheRow($id, fn (): KeyConfig => $this->writeUpdate($id, $parameters));
    }

    /**
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    private function writeUpdate(int $id, KeyUpdate $parameters): KeyConfig
    {
        $config = $this->getById($id);
        $config->setName($parameters->getName());

        if ($parameters->isTitleSupplied()) {
            $config->setTitle($parameters->getTitle() ?? '');
        }

        if ($parameters->isDescriptionSupplied()) {
            $config->setDescription($parameters->getDescription() ?? '');
        }

        if ($parameters->isLocalizedSupplied()) {
            $this->keyLocalizationResolver->setLocalized($config, $parameters->getLocalized());
        }

        if ($parameters->isCustomMetadataSupplied()) {
            $config->setCustomMetadata($parameters->getCustomMetadata());
        }

        $type = $parameters->getType();
        if ($type !== null) {
            $config->setType($type);
        }

        $definition = $parameters->getDefinition();

        try {
            if ($definition !== null) {
                $config->setDefinition(json_encode($definition, JSON_THROW_ON_ERROR));
            }

            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function softDelete(int $id): void
    {
        $config = $this->getById($id);
        $config->setEnabled(false);

        try {
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restore(int $id): KeyConfig
    {
        return $this->writeHoldingTheRow($id, fn (): KeyConfig => $this->writeRestore($id))->getConfig();
    }

    /**
     * Runs with the row held. The model may be a cached one, so it is refreshed from the held row
     * before anything is decided or saved: a stale flag must neither skip the write of a key that is
     * still disabled nor cause one for a key that is already enabled, and `save()` - which writes
     * every column - must not put stale values over what a competing writer stored. The flag is
     * then the only value that differs from the row.
     *
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    private function writeRestore(int $id): KeyConfig
    {
        $config = $this->getById($id);
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT * FROM `%s` WHERE `id` = ? FOR UPDATE', KeyConfigDao::TABLE_NAME_KEYS),
            [$id]
        );

        if (!is_array($row)) {
            throw new NotFoundException('key configuration', $id);
        }

        $storedEnabled = (bool) ($row['enabled'] ?? false);
        unset($row['enabled']);
        $config->setValues($row);

        if ($storedEnabled) {
            $config->setEnabled(true);

            return $config;
        }

        $config->setEnabled(true);

        try {
            $config->save();
        } catch (Exception $e) {
            // the held row is disabled (checked above), so the cached model must report disabled,
            // whatever it said before
            $config->setEnabled(false);

            throw new ElementSavingFailedException($id, $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageForId(
        string $table,
        int $id,
        int $storeId,
        int $pageSize,
        string $sortKey,
        string $sortDir,
        bool $disabledOnly = false,
    ): int {
        $sortKey = in_array($sortKey, self::ALLOWED_SORT_KEYS, true) ? $sortKey : 'name';
        $sortDir = in_array(strtoupper($sortDir), self::ALLOWED_SORT_DIRECTIONS, true)
            ? strtoupper($sortDir)
            : 'ASC';

        $tableName = 'classificationstore_' . ($table === 'groups' ? 'groups' : 'keys');
        // each view counts positions with the condition its listing uses
        $viewCondition = '';
        if ($tableName === 'classificationstore_keys') {
            $viewCondition = $disabledOnly ? ' AND (enabled IS NULL OR enabled = 0)' : ' AND enabled = 1';
        }

        $referenceRow = $this->connection->fetchAssociative(
            sprintf('SELECT `%s` FROM `%s` WHERE id = ?%s', $sortKey, $tableName, $viewCondition),
            [$id]
        );

        if (!$referenceRow) {
            throw new NotFoundException($table, $id);
        }

        $sortValue = $referenceRow[$sortKey];

        $comparison = $sortDir === 'DESC' ? '>' : '<';

        $query = sprintf(
            'SELECT COUNT(*) + 1 as position FROM `%s`' .
            ' WHERE storeId = ? AND (`%s` %s ? OR (`%s` = ? AND id %s ?))%s',
            $tableName,
            $sortKey,
            $comparison,
            $sortKey,
            $comparison,
            $viewCondition,
        );

        try {
            $result = $this->connection->fetchAssociative(
                $query,
                [$storeId, $sortValue, $sortValue, $id]
            );
        } catch (DBALException $e) {
            throw new DatabaseException('Failed to retrieve position for pagination: ' . $e->getMessage(), $e);
        }

        $position = (int) ($result['position'] ?? 1);

        return (int) ceil($position / $pageSize);
    }

    private function applySearchCondition(Listing $listing, FilterParameter $parameters): void
    {
        $searchFilter = $parameters->getSimpleColumnFilterByType(FilterType::SEARCH->value);
        if (!$searchFilter) {
            return;
        }

        $this->searchHelperService->applyKeySearchFilter($listing, $searchFilter->getFilterValue());
    }

    /**
     * Runs one key write with the row it writes held, and answers with the definition that row
     * carried when the lock was taken.
     *
     * The lock is what makes the definition answered with comparable to the one the write leaves
     * behind. Without it the two reads are a check-then-act across two transactions: both requests
     * read the same `indexed`, the second's comparison is made against a value the first has
     * already replaced, and the flag change it is the only witness of is never queued
     * ({@see KeyWriteResult}). Held here rather than at the service boundary because this is where
     * the write is, and a lock the caller takes a call earlier is a lock the next caller has to
     * remember to take.
     *
     * A row that is already gone is not held and reports no definition; the write itself then
     * decides what that means - a 404 for an update, a fresh row for an apply.
     *
     * A database that fails the transaction or the locking read is reported as the saving failure
     * it is, so the two writes keep throwing what they declare and a caller has one exception to
     * handle rather than two.
     *
     * @param Closure(): KeyConfig $write
     *
     * @throws ElementSavingFailedException
     * @throws NotFoundException
     */
    private function writeHoldingTheRow(?int $id, Closure $write): KeyWriteResult
    {
        // an enclosing transaction stays the caller's to finish, the way core's own writes join one
        $ownTransaction = !$this->connection->isTransactionActive();

        try {
            if ($ownTransaction) {
                $this->connection->beginTransaction();
            }

            $definitionBefore = $id === null ? null : $this->holdKey($id);
            $config = $write();

            if ($ownTransaction) {
                $this->connection->commit();
            }
        } catch (Throwable $e) {
            if ($ownTransaction && $this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $e instanceof DBALException
                ? new ElementSavingFailedException($id, $e->getMessage(), $e)
                : $e;
        }

        return new KeyWriteResult($config, $definitionBefore);
    }

    /**
     * Holds the key row and reports the definition it actually carries, read as of now rather than
     * from the snapshot of the transaction this runs in and never from the model cache, so that it
     * is the value a competing writer of this row has to wait behind.
     *
     * @throws DBALException
     */
    private function holdKey(int $id): ?string
    {
        $stored = $this->connection->fetchOne(
            sprintf('SELECT `definition` FROM `%s` WHERE `id` = ? FOR UPDATE', KeyConfigDao::TABLE_NAME_KEYS),
            [$id]
        );

        return is_string($stored) ? $stored : null;
    }
}
