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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Query;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\BooleanFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\IntegerFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\DateFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\MultiSelectFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\NumberRangeFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\FullTextSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\WildcardSearch;

/**
 * @internal
 */
interface DataObjectQueryInterface extends QueryInterface
{
    /**
     * @throws Exception
     */
    public function setClassDefinitionName(string $classDefinitionId): self;

    /**
     * @throws Exception
     */
    public function setClassDefinition(string $classDefinitionId): self;

    public function setClassDefinitionIds(array $classDefinitionIds): self;

    public function booleanFilter(string $fieldName, null|bool $value): self;

    public function classificationStoreFilter(
        string $fieldName,
        string $group,
        BooleanFilter|
        DateFilter|
        FullTextSearch|
        IntegerFilter|
        MultiSelectFilter|
        NumberRangeFilter|
        WildcardSearch $subModifier,
        ?string $locale
    ): self;
}
