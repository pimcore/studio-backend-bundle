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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Repository;

use Pimcore\Bundle\StaticResolverBundle\Models\Metadata\Predefined\PredefinedResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter;
use Pimcore\Bundle\StudioBackendBundle\Metadata\MappedParameter\MetadataParameters;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\UpdatePredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Util\Constant\FilterableFields;
use Pimcore\Model\Metadata\Predefined;
use Pimcore\Model\Metadata\Predefined\Listing;
use function array_key_exists;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class MetadataRepository implements MetadataRepositoryInterface
{
    private const string EXCEPTION_SUBJECT = 'Predefined Metadata';

    public function __construct(private PredefinedResolverInterface $predefinedResolver)
    {
    }

    /**
     * @return Predefined[]
     */
    public function getAllPredefinedMetadata(): array
    {
        return (new Listing())->load();
    }

    public function getAllPredefinedMetadataDefinitions(MetadataParameters $metadataParameters): array
    {
        $listing = new Listing();
        $searchTerm = $metadataParameters->getSearchTerm();

        if ($searchTerm !== null) {
            $listing->setFilter(function (Predefined $predefined) use ($searchTerm) {
                foreach ($predefined->getObjectVars() as $value) {
                    if (stripos((string)$value, $searchTerm) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        $definitions = $listing->getDefinitions();
        $definitions = $this->applyColumnFilters($definitions, $metadataParameters->getColumnFilters());

        return $this->applySorting($definitions, $metadataParameters->getSortFilter());
    }

    public function getPredefinedMetadataByName(string $name): ?Predefined
    {
        return $this->predefinedResolver->getByName($name);
    }

    public function getPredefinedMetadataById(string $id): Predefined
    {
        $predefined = $this->predefinedResolver->getById($id);
        if (!$predefined) {
            throw new NotFoundException(self::EXCEPTION_SUBJECT, $id);
        }

        return $predefined;
    }

    public function createPredefinedMetadata(): Predefined
    {
        if (!(new Predefined())->isWriteable()) {
            throw new NotWriteableException(self::EXCEPTION_SUBJECT);
        }

        $metadata = $this->predefinedResolver->create();
        $metadata->setName('New Definition');
        $metadata->setType('input');
        $metadata->save();

        return $metadata;
    }

    public function updatePredefinedMetadata(
        string $id,
        UpdatePredefinedMetadata $metadata,
    ): Predefined {
        $predefined = $this->getPredefinedMetadataById($id);

        if (!$predefined->isWriteable()) {
            throw new NotWriteableException(self::EXCEPTION_SUBJECT);
        }

        $this->checkForDuplicate(
            $id,
            $metadata->getName(),
            $metadata->getLanguage(),
            $metadata->getTargetSubType(),
        );

        $predefined->setName($metadata->getName());
        $predefined->setDescription($metadata->getDescription());
        $predefined->setType($metadata->getType());
        $predefined->setTargetSubtype($metadata->getTargetSubType());
        $predefined->setData($metadata->getData());
        $predefined->setConfig($metadata->getConfig());
        $predefined->setLanguage($metadata->getLanguage());
        $predefined->setGroup($metadata->getGroup());

        $predefined->minimize();
        $predefined->save();

        return $predefined;
    }

    public function deletePredefinedMetadata(string $id): void
    {
        $predefined = $this->getPredefinedMetadataById($id);

        if (!$predefined->isWriteable()) {
            throw new NotWriteableException(self::EXCEPTION_SUBJECT);
        }

        $predefined->delete();
    }

    /**
     * @return Predefined[]
     */
    public function getPredefinedMetadataByTargetType(
        ?string $subType,
        ?string $group,
    ): array {
        $list = (new Listing())->load();

        return array_filter($list, static function (Predefined $item) use ($subType, $group) {
            if ($subType !== null) {
                $itemSubType = $item->getTargetSubtype();
                if (!empty($itemSubType) && $itemSubType !== $subType) {
                    return false;
                }
            }

            if ($group !== null) {
                $itemGroup = $item->getGroup() ?? '';
                if ($group !== $itemGroup && !($group === 'default' && $itemGroup === '')) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * @param Predefined[] $definitions
     *
     * @return Predefined[]
     */
    private function applyColumnFilters(array $definitions, array $rawColumnFilters): array
    {
        if (empty($rawColumnFilters)) {
            return $definitions;
        }

        $columnFilters = $this->resolveColumnFilters($rawColumnFilters);

        if (empty($columnFilters)) {
            return $definitions;
        }

        return array_values(
            array_filter($definitions, function (Predefined $predefined) use ($columnFilters) {
                foreach ($columnFilters as $columnFilter) {
                    if (!$this->matchesColumnFilter($predefined, $columnFilter)) {
                        return false;
                    }
                }

                return true;
            })
        );
    }

    /**
     * @return ColumnFilter[]
     */
    private function resolveColumnFilters(array $rawColumnFilters): array
    {
        $allowedFields = FilterableFields::values();
        $resolved = [];

        foreach ($rawColumnFilters as $filter) {
            if (!isset($filter['key'], $filter['type']) || !array_key_exists('filterValue', $filter)) {
                continue;
            }

            if (!in_array($filter['key'], $allowedFields, true)) {
                continue;
            }

            $resolved[] = new ColumnFilter(
                $filter['key'],
                $filter['type'],
                $filter['filterValue'],
            );
        }

        return $resolved;
    }

    private function matchesColumnFilter(Predefined $predefined, ColumnFilter $filter): bool
    {
        $value = $this->getPropertyValue($predefined, $filter->getKey());

        return match ($filter->getType()) {
            FilterType::LIKE->value => stripos(
                (string)$value,
                (string)$filter->getFilterValue()
            ) !== false,
            FilterType::EQUALS->value => (string)$value === (string)$filter->getFilterValue(),
            default => true,
        };
    }

    private function getPropertyValue(Predefined $predefined, string $key): mixed
    {
        return match ($key) {
            FilterableFields::NAME->value => $predefined->getName(),
            FilterableFields::DESCRIPTION->value => $predefined->getDescription(),
            FilterableFields::TYPE->value => $predefined->getType(),
            FilterableFields::TARGET_SUBTYPE->value => $predefined->getTargetSubtype(),
            FilterableFields::DATA->value => $predefined->getData(),
            FilterableFields::CONFIG->value => $predefined->getConfig(),
            FilterableFields::LANGUAGE->value => $predefined->getLanguage(),
            FilterableFields::GROUP->value => $predefined->getGroup(),
            default => null,
        };
    }

    /**
     * @param Predefined[] $definitions
     *
     * @return Predefined[]
     */
    private function applySorting(array $definitions, ?SortFilter $sortFilter): array
    {
        if ($sortFilter === null) {
            return $definitions;
        }

        $key = $sortFilter->getKeyWithOutLocale();
        $allowedFields = FilterableFields::values();

        if (!in_array($key, $allowedFields, true)) {
            return $definitions;
        }

        $direction = strtoupper($sortFilter->getDirection()) === 'DESC' ? -1 : 1;

        usort($definitions, function (Predefined $a, Predefined $b) use ($key, $direction) {
            $valueA = $this->getPropertyValue($a, $key);
            $valueB = $this->getPropertyValue($b, $key);

            return strnatcasecmp((string)$valueA, (string)$valueB) * $direction;
        });

        return $definitions;
    }

    private function checkForDuplicate(
        string $id,
        string $name,
        ?string $language,
        ?string $targetSubType,
    ): void {
        foreach ((new Listing())->load() as $item) {
            if ($item->getName() !== $name) {
                continue;
            }

            if ($language !== null && $language !== $item->getLanguage()) {
                continue;
            }

            if ($targetSubType !== null && $targetSubType !== $item->getTargetSubtype()) {
                continue;
            }

            if ($item->getId() !== $id) {
                throw new ApiInvalidArgumentException(
                    sprintf(
                        'Predefined metadata with name "%s", language "%s" and target subtype "%s" already exists',
                        $name,
                        $language ?? '',
                        $targetSubType ?? '',
                    ),
                );
            }
        }
    }
}
