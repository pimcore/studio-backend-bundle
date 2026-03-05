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

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Connection;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing;
use function in_array;
use function json_encode;
use function sprintf;
use function strtoupper;

/**
 * @internal
 */
final readonly class KeyRepository implements KeyRepositoryInterface
{
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

    public function __construct(
        private KeyConfigResolverInterface $keyConfigResolver,
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
        $listing->addConditionParam('`storeId` = :storeId', ['storeId' => $storeId]);

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
    public function create(string $name, int $storeId): KeyConfig
    {
        $definition = [
            'fieldtype' => 'input',
            'name' => $name,
            'title' => $name,
            'datatype' => 'data',
        ];

        $config = new KeyConfig();
        $config->setName($name);
        $config->setTitle($name);
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
    public function update(
        int $id,
        string $name,
        ?string $title,
        ?string $description,
        ?string $type,
        ?array $definition,
    ): KeyConfig {
        $config = $this->getById($id);
        $config->setName($name);
        $config->setTitle($title ?? $config->getTitle());
        $config->setDescription($description ?? $config->getDescription());

        if ($type !== null) {
            $config->setType($type);
        }
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
    public function getPageForId(
        string $table,
        int $id,
        int $storeId,
        int $pageSize,
        string $sortKey,
        string $sortDir,
    ): int {
        $sortKey = in_array($sortKey, self::ALLOWED_SORT_KEYS, true) ? $sortKey : 'name';
        $sortDir = in_array(strtoupper($sortDir), self::ALLOWED_SORT_DIRECTIONS, true)
            ? strtoupper($sortDir)
            : 'ASC';

        $tableName = 'classificationstore_' . ($table === 'groups' ? 'groups' : 'keys');
        $enabledCondition = $tableName === 'classificationstore_keys' ? ' AND enabled = 1' : '';

        $referenceRow = $this->connection->fetchAssociative(
            sprintf('SELECT `%s` FROM `%s` WHERE id = ?', $sortKey, $tableName),
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
            $enabledCondition,
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

        if ($searchFilter->getFilterValue() === '' || $searchFilter->getFilterValue() === null) {
            return;
        }

        $this->searchHelperService->applyKeySearchFilter($listing, $searchFilter->getFilterValue());
    }
}
