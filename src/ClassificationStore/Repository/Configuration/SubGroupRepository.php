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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing as GroupConfigListing;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Listing;
use function implode;
use function sprintf;

/**
 * @internal
 */
final readonly class SubGroupRepository implements SubGroupRepositoryInterface
{
    private const string STORE_CONDITION = '`storeId` = :storeId';

    private const string STORE_ID_PARAMETER = 'storeId';

    public function __construct(
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
        $listing->addConditionParam(self::STORE_CONDITION, [self::STORE_ID_PARAMETER => $storeId]);

        $this->applySearchCondition($listing, $parameters);
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getByStoreId(int $storeId): array
    {
        $listing = new Listing();
        $listing->addConditionParam(self::STORE_CONDITION, [self::STORE_ID_PARAMETER => $storeId]);
        $listing->setOrderKey('id');
        $listing->setOrder('ASC');

        return $listing->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): SubGroupConfig
    {
        $config = $this->findOne('`id` = :id', ['id' => $id]);

        if (!$config) {
            throw new NotFoundException('sub group configuration', $id);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, int $storeId, ?string $description): SubGroupConfig
    {
        $this->assertNameIsFree($name, $storeId, null);

        $config = new SubGroupConfig();
        $config->setName($name);
        $config->setStoreId($storeId);
        $config->setDescription($description);

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
    public function update(int $id, string $name, ?string $description): SubGroupConfig
    {
        $config = $this->getById($id);

        $this->assertNameIsFree($name, $config->getStoreId(), $id);

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

        $this->assertNoAssignmentCarriesTheIdentifier($config);

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

    /**
     * @throws InvalidArgumentException
     */
    private function assertNameIsFree(string $name, int $storeId, ?int $exceptId): void
    {
        $existing = $this->findOne(
            self::STORE_CONDITION . ' AND `name` = :name',
            [self::STORE_ID_PARAMETER => $storeId, 'name' => $name]
        );

        if (!$existing || $existing->getId() === $exceptId) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf('Sub group with the name "%s" already exists in store %d', $name, $storeId)
        );
    }

    /**
     * The relation column carries the immutable identifier, so an entry that is still named by an
     * assignment cannot be dropped without orphaning it.
     *
     * @throws ConflictException
     */
    private function assertNoAssignmentCarriesTheIdentifier(SubGroupConfig $config): void
    {
        $identifier = $config->getIdentifier();
        if ($identifier === null) {
            return;
        }

        $listing = new GroupConfigListing();
        $listing->addConditionParam(
            self::STORE_CONDITION
            . ' AND `id` IN (SELECT `groupId` FROM `' . KeyGroupRelationDao::TABLE_NAME_RELATIONS . '`'
            . ' WHERE `subGroup` = :subGroup)',
            [self::STORE_ID_PARAMETER => $config->getStoreId(), 'subGroup' => $identifier]
        );
        $listing->setOrderKey('name');
        $listing->setOrder('ASC');

        $groupNames = [];
        foreach ($listing->load() as $groupConfig) {
            $groupNames[] = $groupConfig->getName();
        }

        if ($groupNames === []) {
            return;
        }

        throw new ConflictException(
            sprintf(
                'Sub group "%s" is still used by the keys of the group(s) "%s"',
                $config->getName(),
                implode('", "', $groupNames)
            )
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function findOne(string $condition, array $parameters): ?SubGroupConfig
    {
        $listing = new Listing();
        $listing->addConditionParam($condition, $parameters);
        $listing->setLimit(1);

        return $listing->load()[0] ?? null;
    }
}
