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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\CollectionConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\IdentifierOrderedChunkTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Listing;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use function sprintf;

/**
 * @internal
 */
final readonly class CollectionRepository implements CollectionRepositoryInterface
{
    use IdentifierOrderedChunkTrait;

    public function __construct(
        private CollectionConfigResolverInterface $collectionConfigResolver,
        private ListingFilterInterface $listingFilter,
        private SearchHelperServiceInterface $searchHelperService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getListing(FilterParameter $parameters, int $storeId): Listing
    {
        $listing = new Listing();
        // own parameter name, so a client supplied equals filter on `storeId` narrows the route's
        // store binding instead of replacing the condition Pimcore keys by its own string
        $listing->addConditionParam('`storeId` = :scopeStoreId', ['scopeStoreId' => $storeId]);

        $this->applySearchCondition($listing, $parameters);
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): CollectionConfig
    {
        $config = $this->collectionConfigResolver->getById($id);

        if (!$config) {
            throw new NotFoundException('collection configuration', $id);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkByStore(int $storeId, int $limit, int $offset): array
    {
        $listing = new Listing();
        $listing->addConditionParam('`storeId` = :scopeStoreId', ['scopeStoreId' => $storeId]);
        $this->applyIdentifierOrderedChunk($listing, $limit, $offset);

        return $listing->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkAfterId(int $storeId, int $afterId, int $limit): array
    {
        $listing = new Listing();
        $listing->addConditionParam('`storeId` = :scopeStoreId', ['scopeStoreId' => $storeId]);
        $listing->addConditionParam('`id` > :afterId', ['afterId' => $afterId]);
        $this->applyIdCursorChunk($listing, $limit);

        return $listing->load();
    }

    /**
     * {@inheritdoc}
     */
    public function saveFromArtifact(
        ?CollectionConfig $config,
        int $storeId,
        string $identifier,
        string $name,
        ?string $description,
    ): CollectionConfig {
        $config ??= new CollectionConfig();
        $config->setStoreId($storeId);
        $config->setName($name);
        $config->setDescription($description ?? '');

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
    public function create(string $name, int $storeId): CollectionConfig
    {
        $existing = $this->collectionConfigResolver->getByName($name, $storeId);

        if ($existing) {
            throw new InvalidArgumentException(
                sprintf('Collection with the name "%s" already exists in store %d', $name, $storeId)
            );
        }

        $config = new CollectionConfig();
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
    public function update(int $id, string $name, ?string $description): CollectionConfig
    {
        $config = $this->getById($id);
        $config->setName($name);
        $config->setDescription($description ?? '');

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

        $relations = new CollectionGroupRelation\Listing();
        $relations->setCondition('colId = ?', [$id]);

        foreach ($relations->load() as $relation) {
            $relation->delete();
        }

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
