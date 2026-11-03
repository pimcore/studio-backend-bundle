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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\IdentifierOrderedChunkTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing;
use function sprintf;

/**
 * @internal
 */
final readonly class GroupRepository implements GroupRepositoryInterface
{
    use IdentifierOrderedChunkTrait;

    /**
     * Own parameter name, so a client supplied equals filter on `storeId` narrows the route's store
     * binding instead of replacing the condition Pimcore keys by its own string.
     */
    private const string STORE_SCOPE_PARAMETER = 'scopeStoreId';

    private const string STORE_SCOPE_CONDITION = '`storeId` = :' . self::STORE_SCOPE_PARAMETER;

    public function __construct(
        private GroupConfigResolverInterface $groupConfigResolver,
        private ListingFilterInterface $listingFilter,
        private SearchHelperServiceInterface $searchHelperService,
        private Connection $connection,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getListing(FilterParameter $parameters, int $storeId): Listing
    {
        $listing = new Listing();
        $listing->addConditionParam(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);

        $this->applySearchCondition($listing, $parameters);
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): GroupConfig
    {
        $config = $this->groupConfigResolver->getById($id);

        if (!$config) {
            throw new NotFoundException('group configuration', $id);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkAfterIdentifier(
        int $storeId,
        ?string $afterIdentifier,
        int $afterId,
        int $limit,
    ): array {
        $listing = new Listing();
        $listing->addConditionParam(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);
        $this->applyIdentifierCursorChunk($listing, $limit, $afterIdentifier, $afterId);

        return $listing->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkAfterId(
        int $storeId,
        int $afterId,
        int $limit,
        ?bool $withIdentifier = null,
    ): array {
        $listing = new Listing();
        $listing->addConditionParam(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);
        $listing->addConditionParam('`id` > :afterId', ['afterId' => $afterId]);
        $this->applyIdentifierPhase($listing, $withIdentifier);
        $this->applyIdCursorChunk($listing, $limit);

        return $listing->load();
    }

    /**
     * {@inheritdoc}
     */
    public function holdGroupsOfStore(int $storeId): void
    {
        try {
            // ascending id, so that two holders of differently scoped sets still agree on an order
            $this->connection->fetchFirstColumn(
                sprintf(
                    'SELECT `id` FROM `%s` WHERE `storeId` = ? ORDER BY `id` FOR UPDATE',
                    GroupConfigDao::TABLE_NAME_GROUPS
                ),
                [$storeId]
            );
        } catch (DBALException $e) {
            throw new DatabaseException('Failed to hold the groups of the store: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isNameTaken(int $storeId, string $name): bool
    {
        try {
            return (bool) $this->groupConfigResolver->getByName($name, $storeId);
        } catch (Exception $e) {
            throw new DatabaseException('Failed to check the group name: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveFromArtifact(
        ?GroupConfig $config,
        int $storeId,
        string $identifier,
        string $name,
        ?string $description,
    ): GroupConfig {
        $config ??= new GroupConfig();
        $config->setStoreId($storeId);
        $config->setName($name);
        $config->setDescription($description);

        try {
            // before the save, so that a new row carries the file's identifier instead of a fresh one
            $config->setIdentifier($identifier);
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($config->getId(), $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, int $storeId): GroupConfig
    {
        try {
            $existing = $this->groupConfigResolver->getByName($name, $storeId);
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        if ($existing) {
            throw new InvalidArgumentException(
                sprintf('Group with the name "%s" already exists in store %d', $name, $storeId)
            );
        }

        $config = new GroupConfig();
        $config->setName($name);
        $config->setStoreId($storeId);

        try {
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, string $name, ?string $description): GroupConfig
    {
        $config = $this->getById($id);
        $config->setName($name);
        $config->setDescription($description);

        try {
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): void
    {
        $config = $this->getById($id);
        $config->delete();
    }

    private function applySearchCondition(Listing $listing, FilterParameter $parameters): void
    {
        $searchFilter = $parameters->getSimpleColumnFilterByType(FilterType::SEARCH->value);
        if (!$searchFilter) {
            return;
        }

        $this->searchHelperService->applySearchTermFilter($listing, $searchFilter->getFilterValue());
    }
}
