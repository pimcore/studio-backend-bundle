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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\DataObject\Classificationstore;

use Codeception\Test\Unit;
use Error;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyFilterResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexFlagResolver;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore\UnfilterableKeyGuard;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreFilterValueResolver;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\MultiValueServiceResolver;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValueRange;
use Pimcore\Model\DataObject\ClassDefinition\Data\Wysiwyg;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * A Classification Store filter the grid cannot apply is refused, never answered with the unfiltered page.
 *
 * @internal
 */
final class UnfilterableKeyGuardTest extends Unit
{
    private const string KEY_NAME = 'weight';

    private const string STRING_TYPE = 'classificationstore.string';

    public function testAFilterTypeNoFilterConsumesIsRefusedNamingTheKey(): void
    {
        $this->assertRefused(
            $this->filterOf('classificationstore.table'),
            HttpResponseErrorKeys::FILTER_TYPE_UNSUPPORTED,
            'cs'
        );
    }

    public function testATypeDeclaringNoFilterIsRefusedEvenWhereItIsOneOfTheEleven(): void
    {
        $this->assertRefused(
            $this->filterOf('classificationstore.quantity_value_range'),
            HttpResponseErrorKeys::FILTER_TYPE_UNSUPPORTED,
            'cs'
        );
    }

    public function testAKeyExcludedFromTheSearchIndexIsRefusedNamingIt(): void
    {
        $this->assertRefused(
            $this->filterOf(self::STRING_TYPE),
            HttpResponseErrorKeys::FILTER_KEY_NOT_INDEXED,
            self::KEY_NAME,
            '{"fieldtype":"input","indexed":false}'
        );
    }

    public function testAKeyOfATypeDeclaringNoFilterIsRefusedWhateverTypeTheFilterNames(): void
    {
        $this->assertRefused(
            $this->filterOf(self::STRING_TYPE),
            HttpResponseErrorKeys::FILTER_TYPE_UNSUPPORTED,
            self::KEY_NAME,
            '{"fieldtype":"quantityValueRange"}',
            new QuantityValueRange()
        );
    }

    public function testAFilterTypeThatDoesNotBelongToTheKeyIsRefusedNamingIt(): void
    {
        $this->assertRefused(
            $this->filterOf('classificationstore.number'),
            HttpResponseErrorKeys::FILTER_TYPE_UNSUPPORTED,
            'classificationstore.number'
        );
    }

    public function testAVariantTheKeyDoesNotDeclareIsRefusedNamingIt(): void
    {
        $this->assertRefused(
            $this->filterOf(self::STRING_TYPE, 'Imperial'),
            HttpResponseErrorKeys::FILTER_VARIANT_UNDECLARED,
            'Imperial'
        );
    }

    public function testADeclaredVariantIsApplied(): void
    {
        $query = $this->makeEmpty(DataObjectQueryInterface::class);

        $this->assertSame(
            $query,
            $this->guard('{"fieldtype":"input"}', true)->apply($this->filterOf(self::STRING_TYPE, 'Metric'), $query)
        );
    }

    public function testAnIndexedKeyOfTheSameTypeIsAppliedAsBefore(): void
    {
        $query = $this->makeEmpty(DataObjectQueryInterface::class);

        $this->assertSame($query, $this->guard('{"fieldtype":"input"}')->apply($this->filterOf(self::STRING_TYPE), $query));
    }

    public function testAnUnmatchedFilterOfAnotherTypeIsLeftAsItWas(): void
    {
        $query = $this->makeEmpty(DataObjectQueryInterface::class);

        $this->assertSame($query, $this->guard()->apply($this->filterOf('system.unknown'), $query));
    }

    private function assertRefused(
        FilterParameter $parameter,
        HttpResponseErrorKeys $errorKey,
        string $namedKey,
        string $definition = '{"fieldtype":"input"}',
        Data $keyDefinition = new Input()
    ): void {
        try {
            $this->guard($definition, false, $keyDefinition)->apply($parameter, $this->makeEmpty(DataObjectQueryInterface::class));
            $this->fail('Expected the filter to be refused.');
        } catch (AbstractApiException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame($errorKey->value, $exception->getErrorKey());
            $this->assertStringContainsString($namedKey, $exception->getMessage());
        }
    }

    private function filterOf(string $type, ?string $variant = null): FilterParameter
    {
        return new FilterParameter(columnFilters: [[
            'key' => 'cs',
            'type' => $type,
            'filterValue' => [
                'groupId' => 1,
                'keyId' => 2,
                'value' => 'x',
                ...($variant !== null ? ['variant' => $variant] : []),
            ],
        ]]);
    }

    private function variantKeyConfig(bool $multiValue = false): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setType($multiValue ? MultiValueServiceResolver::MULTI_VALUE_TYPE : 'input');
        $keyConfig->setDefinition('{"variants":["Metric"]}');

        return $keyConfig;
    }

    private function guard(
        string $definition = '{"fieldtype":"input"}',
        bool $multiValue = false,
        Data $keyDefinition = new Input()
    ): UnfilterableKeyGuard {
        $relation = new KeyGroupRelation();
        $relation->setName(self::KEY_NAME);
        $relation->setType('input');
        $relation->setDefinition($definition);

        return new UnfilterableKeyGuard(
            $this->keyFilterResolver($keyDefinition),
            $this->makeEmpty(KeyRelationResolverInterface::class, ['getByGroupAndKey' => $relation]),
            new ClassificationStoreFilterValueResolver($this->makeEmpty(ColumnReferenceServiceInterface::class)),
            $this->makeEmpty(KeyConfigResolverInterface::class, ['getById' => $this->variantKeyConfig($multiValue)]),
            new KeyQualifierResolver(new MultiValueServiceResolver())
        );
    }

    private function keyFilterResolver(Data $definition): KeyFilterResolver
    {
        return new KeyFilterResolver(
            $this->makeEmpty(ServiceResolverInterface::class, ['getFieldDefinitionFromJson' => $definition]),
            new SearchIndexFlagResolver()
        );
    }

    public function testAFilterValueThatIsNotAnObjectIsRefusedNotLeftToTheFilter(): void
    {
        $parameter = new FilterParameter(columnFilters: [[
            'key' => 'cs',
            'type' => self::STRING_TYPE,
            'filterValue' => 'abc',
        ]]);

        $this->assertRefused($parameter, HttpResponseErrorKeys::INVALID_ARGUMENT, 'cs');
    }

    public function testAMultiValueKeyIsRoutedByTheTypeItWraps(): void
    {
        $multiValue = new MultiValue();
        $multiValue->setDelegate(new Numeric());
        $resolver = $this->keyFilterResolver($multiValue);

        $this->assertSame(['classificationstore.number'], $resolver->getFilterTypes($multiValue));
    }

    public function testADefinitionThatCannotBeRebuiltIsNotReportedAsAnUnsupportedType(): void
    {
        $relation = new KeyGroupRelation();
        $relation->setType('legacy');
        $relation->setDefinition('{"fieldtype":"legacy"}');
        $resolver = new KeyFilterResolver(
            $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromJson' => static function (): never {
                    throw new Error('no data class');
                },
            ]),
            new SearchIndexFlagResolver()
        );

        $this->assertSame(
            \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\FilterUnavailableReason::RESOLUTION_FAILED,
            $resolver->getFilterUnavailableReason($relation)
        );
    }

    public function testTwoKeyTypesDeclaringTheSameFilterRouteToTheSameFilterType(): void
    {
        $input = $this->keyFilterResolver(new Input())->getFilterTypes(new Input());
        $wysiwyg = $this->keyFilterResolver(new Input())->getFilterTypes(new Wysiwyg());

        $this->assertSame([self::STRING_TYPE], $input);
        $this->assertSame($input, $wysiwyg);
        $this->assertSame([], $this->keyFilterResolver(new Input())->getFilterTypes(new QuantityValueRange()));
    }
}
