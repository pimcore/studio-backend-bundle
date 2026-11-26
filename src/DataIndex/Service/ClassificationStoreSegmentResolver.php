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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\MappingProperty;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\ClassificationStore\IndexSegmentServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\ClassificationStore\KeyLocaleServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ClassIdParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\ClassificationStoreFilterValue;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use function sprintf;

/**
 * @internal
 */
final readonly class ClassificationStoreSegmentResolver implements ClassificationStoreSegmentResolverInterface
{
    public function __construct(
        private IndexSegmentServiceInterface $indexSegmentService,
        private KeyLocaleServiceInterface $keyLocaleService,
        private ClassDefinitionResolverInterface $classDefinitionResolver,
    ) {
    }

    public function resolveGroupSegment(ClassificationStoreFilterValue $filterValue): string
    {
        $segment = $this->indexSegmentService->getGroupSegment($filterValue->getGroupId());
        if ($segment === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'The search index addresses no field of the Classification Store group %d, '
                    . 'so it cannot be filtered.',
                    $filterValue->getGroupId()
                ),
                errorKey: HttpResponseErrorKeys::FILTER_KEY_NOT_INDEXED->value
            );
        }

        return $segment;
    }

    public function resolveFieldName(ClassificationStoreFilterValue $filterValue): string
    {
        $segment = $this->indexSegmentService->getKeySegment($filterValue->getKeyId(), $filterValue->getVariant());
        if ($segment === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'The search index addresses no field of the Classification Store key %d, '
                    . 'so it cannot be filtered.',
                    $filterValue->getKeyId()
                ),
                errorKey: HttpResponseErrorKeys::FILTER_KEY_NOT_INDEXED->value
            );
        }

        return $segment;
    }

    public function resolveLocale(
        ClassificationStoreFilterValue $filterValue,
        ColumnFilter $column,
        mixed $parameters = null
    ): string {
        return $this->keyLocaleService->getLocaleForKey(
            $filterValue->getKeyId(),
            $this->resolveFieldDefinition($column->getKeyWithOutLocale(), $parameters),
            $column->getLocale() ?? MappingProperty::NOT_LOCALIZED_KEY
        );
    }

    /**
     * The class field the column names, which a key without a localization flag of its own inherits
     * that flag from.
     *
     * @throws InvalidArgumentException
     */
    private function resolveFieldDefinition(string $fieldName, mixed $parameters): Classificationstore
    {
        $classId = $parameters instanceof ClassIdParametersInterface ? $parameters->getClassId() : null;
        $fieldDefinition = $classId === null
            ? null
            : $this->classDefinitionResolver->getById($classId)?->getFieldDefinition($fieldName);

        if (!$fieldDefinition instanceof Classificationstore) {
            throw new InvalidArgumentException(
                sprintf(
                    'No class of this request holds the Classification Store field "%s", so the language '
                    . 'the key is indexed under cannot be resolved.',
                    $fieldName
                ),
                errorKey: HttpResponseErrorKeys::FILTER_DEFINITION_UNRESOLVABLE->value
            );
        }

        return $fieldDefinition;
    }
}
