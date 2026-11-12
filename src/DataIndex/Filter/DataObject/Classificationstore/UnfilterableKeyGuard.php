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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyFilterResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\FilterUnavailableReason;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreFilterValueResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function str_starts_with;

/**
 * Refuses a Classification Store column filter the grid cannot apply, rather than letting it be
 * dropped and answering every row: a filter type no filter of this bundle consumes, and a key that is
 * excluded from the search index by its own definition. Filters of any other type are left alone.
 *
 * @internal
 */
final class UnfilterableKeyGuard implements FilterInterface
{
    private const string TYPE_PREFIX = 'classificationstore.';

    public function __construct(
        private readonly KeyFilterResolverInterface $keyFilterResolver,
        private readonly KeyRelationResolverInterface $keyRelationResolver,
        private readonly ClassificationStoreFilterValueResolverInterface $filterValueResolver,
        private readonly KeyConfigResolverInterface $keyConfigResolver,
        private readonly KeyQualifierResolverInterface $keyQualifierResolver,
    ) {
    }

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface || !$query instanceof DataObjectQueryInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilters() as $filter) {
            $type = $filter['type'] ?? null;
            if (!is_string($type) || !str_starts_with($type, self::TYPE_PREFIX)) {
                continue;
            }

            $column = new ColumnFilter(
                (string) ($filter['key'] ?? ''),
                $type,
                $filter['filterValue'] ?? [],
                $filter['locale'] ?? null
            );

            if (!$this->keyFilterResolver->isSupportedFilterType($type)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'No filter is supported for the Classification Store key "%s" of type "%s".',
                        $column->getKeyWithOutLocale(),
                        $type
                    ),
                    errorKey: HttpResponseErrorKeys::FILTER_TYPE_UNSUPPORTED->value
                );
            }

            $this->assertFilterable($column, $type, $parameters);
        }

        return $query;
    }

    private function assertFilterable(
        ColumnFilter $column,
        string $type,
        ColumnFiltersParameterInterface $parameters
    ): void {
        $rawValue = $column->getFilterValue();
        if (!is_array($rawValue)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The filter value of the Classification Store key "%s" has to be an object.',
                    $column->getKeyWithOutLocale()
                )
            );
        }

        $filterValue = $this->filterValueResolver->resolve($rawValue, $parameters, $column->getKeyWithOutLocale());
        $relation = $this->keyRelationResolver->getByGroupAndKey($filterValue->getGroupId(), $filterValue->getKeyId());

        $reason = $this->keyFilterResolver->getFilterUnavailableReason($relation);
        if ($reason === FilterUnavailableReason::INDEX_EXCLUDED) {
            throw new InvalidArgumentException(
                sprintf(
                    'The Classification Store key "%s" is excluded from the search index and cannot be filtered.',
                    $relation->getName()
                ),
                errorKey: HttpResponseErrorKeys::FILTER_KEY_NOT_INDEXED->value
            );
        }

        if ($reason === FilterUnavailableReason::RESOLUTION_FAILED) {
            throw new InvalidArgumentException(
                sprintf(
                    'The definition of the Classification Store key "%s" cannot be read, so it cannot be filtered.',
                    $relation->getName()
                ),
                errorKey: HttpResponseErrorKeys::FILTER_DEFINITION_UNRESOLVABLE->value
            );
        }

        if ($reason !== null) {
            throw new InvalidArgumentException(
                sprintf(
                    'No filter is supported for the Classification Store key "%s": its type declares none.',
                    $relation->getName()
                ),
                errorKey: HttpResponseErrorKeys::FILTER_TYPE_UNSUPPORTED->value
            );
        }

        if (!in_array($type, $this->keyFilterResolver->getFilterTypesOf($relation), true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The Classification Store key "%s" cannot be filtered with the filter type "%s".',
                    $relation->getName(),
                    $type
                ),
                errorKey: HttpResponseErrorKeys::FILTER_TYPE_UNSUPPORTED->value
            );
        }

        $this->assertVariantDeclared($filterValue->getVariant(), $filterValue->getKeyId(), $relation->getName());
    }

    private function assertVariantDeclared(?string $variant, int $keyId, string $keyName): void
    {
        if ($variant === null) {
            return;
        }

        $keyConfig = $this->keyConfigResolver->getById($keyId);
        $variants = $keyConfig instanceof KeyConfig ? $this->keyQualifierResolver->getVariants($keyConfig) : null;
        if ($variants === null || !in_array($variant, $variants, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The Classification Store key "%s" does not declare the variant "%s", so it cannot be filtered by it.',
                    $keyName,
                    $variant
                ),
                errorKey: HttpResponseErrorKeys::FILTER_VARIANT_UNDECLARED->value
            );
        }
    }
}
