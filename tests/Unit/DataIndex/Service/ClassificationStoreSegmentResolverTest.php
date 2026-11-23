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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Service;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\ClassificationStore\IndexSegmentServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\ClassificationStore\KeyLocaleService;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ClassIdParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreSegmentResolver;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\ClassificationStoreFilterValue;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\LocalizationResolver;

/**
 * @internal
 */
final class ClassificationStoreSegmentResolverTest extends Unit
{
    private const GROUP_ID = 7;

    private const KEY_ID = 8;

    private const CLASS_ID = 'CAR';

    private const FIELD_NAME = 'classificationStoreField';

    private const LANGUAGE = 'de';

    private const NON_LOCALIZED_NODE = 'default';

    private const COLUMN_TYPE = 'classificationstore.string';

    protected function _before(): void
    {
        DefinitionCache::clear(null);
    }

    public function testTheSegmentsTheIndexAnswersAreHandedOn(): void
    {
        $resolver = $this->createResolver('dimensions', 'weight');
        $filterValue = $this->createFilterValue();

        $this->assertSame('dimensions', $resolver->resolveGroupSegment($filterValue));
        $this->assertSame('weight', $resolver->resolveFieldName($filterValue));
    }

    public function testTheVariantReachesTheIndexRatherThanBeingAppendedHere(): void
    {
        $seen = null;
        $resolver = new ClassificationStoreSegmentResolver(
            $this->makeEmpty(IndexSegmentServiceInterface::class, [
                'getKeySegment' => static function (int $keyId, ?string $variant) use (&$seen): string {
                    $seen = $variant;

                    return 'weight_Metric';
                },
            ]),
            new KeyLocaleService(new LocalizationResolver($this->makeEmpty(Connection::class))),
            $this->makeEmpty(ClassDefinitionResolverInterface::class)
        );

        $this->assertSame('weight_Metric', $resolver->resolveFieldName($this->createFilterValue('Metric')));
        $this->assertSame('Metric', $seen);
    }

    public function testAGroupTheIndexAddressesNoFieldOfIsRefused(): void
    {
        $resolver = $this->createResolver(null, 'weight');

        $this->expectException(InvalidArgumentException::class);
        $resolver->resolveGroupSegment($this->createFilterValue());
    }

    public function testAKeyTheIndexAddressesNoFieldOfIsRefused(): void
    {
        $resolver = $this->createResolver('dimensions', null);

        try {
            $resolver->resolveFieldName($this->createFilterValue());
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(HttpResponseErrorKeys::FILTER_KEY_NOT_INDEXED->value, $exception->getErrorKey());

            return;
        }

        $this->fail('A key with no addressable field has to be refused.');
    }

    public function testALocalizedKeyIsAddressedUnderTheColumnLanguage(): void
    {
        $this->registerKey(localized: true);

        $this->assertSame(
            self::LANGUAGE,
            $this->createLocaleResolver(fieldLocalized: false)->resolveLocale(
                $this->createFilterValue(),
                $this->createColumn(self::LANGUAGE),
                $this->createParameters(self::CLASS_ID)
            )
        );
    }

    public function testANonLocalizedKeyIsAddressedUnderTheDefaultNodeDespiteTheColumnLanguage(): void
    {
        $this->registerKey(localized: false);

        $this->assertSame(
            self::NON_LOCALIZED_NODE,
            $this->createLocaleResolver(fieldLocalized: true)->resolveLocale(
                $this->createFilterValue(),
                $this->createColumn(self::LANGUAGE),
                $this->createParameters(self::CLASS_ID)
            )
        );
    }

    public function testAKeyWithoutAFlagOfItsOwnFollowsTheField(): void
    {
        $this->registerKey(localized: null);

        $this->assertSame(
            self::LANGUAGE,
            $this->createLocaleResolver(fieldLocalized: true)->resolveLocale(
                $this->createFilterValue(),
                $this->createColumn(self::LANGUAGE),
                $this->createParameters(self::CLASS_ID)
            )
        );
    }

    public function testAColumnWithoutALanguageAddressesTheNonLocalizedNode(): void
    {
        $this->registerKey(localized: false);

        $this->assertSame(
            self::NON_LOCALIZED_NODE,
            $this->createLocaleResolver(fieldLocalized: false)->resolveLocale(
                $this->createFilterValue(),
                $this->createColumn(null),
                $this->createParameters(self::CLASS_ID)
            )
        );
    }

    public function testAFilterNamingNoClassIsRefusedRatherThanGuessingTheNode(): void
    {
        $this->registerKey(localized: false);

        try {
            $this->createLocaleResolver(fieldLocalized: false)->resolveLocale(
                $this->createFilterValue(),
                $this->createColumn(self::LANGUAGE),
                $this->createParameters(null)
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                HttpResponseErrorKeys::FILTER_DEFINITION_UNRESOLVABLE->value,
                $exception->getErrorKey()
            );

            return;
        }

        $this->fail('A filter whose field cannot be resolved has to be refused.');
    }

    /**
     * The localization rule is the search index bundle's own, so the resolver is built on its real
     * service and only the segment side is stubbed.
     */
    private function createLocaleResolver(bool $fieldLocalized): ClassificationStoreSegmentResolver
    {
        $fieldDefinition = new Classificationstore();
        $fieldDefinition->setLocalized($fieldLocalized);

        $classDefinition = new ClassDefinition();
        $classDefinition->addFieldDefinition(self::FIELD_NAME, $fieldDefinition);

        return new ClassificationStoreSegmentResolver(
            $this->makeEmpty(IndexSegmentServiceInterface::class),
            new KeyLocaleService(new LocalizationResolver($this->makeEmpty(Connection::class))),
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => $classDefinition,
            ])
        );
    }

    private function registerKey(?bool $localized): void
    {
        $keyConfig = (new KeyConfig())->setId(self::KEY_ID);
        $keyConfig->setLocalized($localized);

        DefinitionCache::put($keyConfig);
    }

    private function createColumn(?string $locale): ColumnFilter
    {
        return new ColumnFilter(self::FIELD_NAME, self::COLUMN_TYPE, [], $locale);
    }

    private function createParameters(?string $classId): ClassIdParametersInterface
    {
        return $this->makeEmpty(ClassIdParametersInterface::class, ['getClassId' => $classId]);
    }

    private function createResolver(?string $groupSegment, ?string $keySegment): ClassificationStoreSegmentResolver
    {
        return new ClassificationStoreSegmentResolver(
            $this->makeEmpty(IndexSegmentServiceInterface::class, [
                'getGroupSegment' => $groupSegment,
                'getKeySegment' => $keySegment,
            ]),
            new KeyLocaleService(new LocalizationResolver($this->makeEmpty(Connection::class))),
            $this->makeEmpty(ClassDefinitionResolverInterface::class)
        );
    }

    private function createFilterValue(?string $variant = null): ClassificationStoreFilterValue
    {
        return new ClassificationStoreFilterValue(self::GROUP_ID, self::KEY_ID, 'needle', $variant);
    }
}
