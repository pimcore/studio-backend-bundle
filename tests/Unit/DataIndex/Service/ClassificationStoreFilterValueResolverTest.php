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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreFilterValueResolver;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\ClassificationStoreFilterValue;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;

/**
 * @internal
 */
final class ClassificationStoreFilterValueResolverTest extends Unit
{
    private const string CLASS_ID = 'EV';

    private const array IDENTIFIERS = ['groupIdentifier' => 'product-data', 'keyIdentifier' => 'weight'];

    public function testIdentifiersWinOverAWrongIntegerPair(): void
    {
        $value = $this->getValue(
            ['groupId' => 99, 'keyId' => 98, 'value' => 'needle', ...self::IDENTIFIERS],
            $this->createParameter(),
            new ClassificationStoreConfig(5, 6)
        );

        $this->assertSame([5, 6, 'needle'], [$value->getGroupId(), $value->getKeyId(), $value->getValue()]);
    }

    /**
     * A filter saved with identifiers alone carries no pair to fall back to, so it only searches at all
     * because the parameters name the class whose field's store the identifiers are resolved in.
     */
    public function testIdentifierOnlyValueResolvesWhenTheParametersNameTheClass(): void
    {
        $value = $this->getValue(
            ['value' => 'needle', ...self::IDENTIFIERS],
            $this->createParameter(),
            new ClassificationStoreConfig(5, 6)
        );

        $this->assertSame([5, 6], [$value->getGroupId(), $value->getKeyId()]);
    }

    public function testIntegerPairIsUsedWhenTheValueCarriesNoIdentifiers(): void
    {
        $value = $this->getValue(['groupId' => 7, 'keyId' => 8, 'value' => 'needle'], $this->createParameter(), null);

        $this->assertSame([7, 8], [$value->getGroupId(), $value->getKeyId()]);
    }

    public function testAVariantIsCarriedAndNamesTheSiblingIndexField(): void
    {
        $value = $this->getValue(
            ['groupId' => 7, 'keyId' => 8, 'value' => 'needle', 'variant' => 'Metric'],
            $this->createParameter(),
            null
        );

        $this->assertSame('Metric', $value->getVariant());
        $this->assertSame('weight_Metric', $value->getIndexFieldName('weight'));
    }

    public function testNoVariantAddressesTheKeysOwnField(): void
    {
        $value = $this->getValue(['groupId' => 7, 'keyId' => 8, 'value' => 'needle'], $this->createParameter(), null);

        $this->assertNull($value->getVariant());
        $this->assertSame('weight', $value->getIndexFieldName('weight'));
    }

    public function testAnEmptyVariantIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getValue(
            ['groupId' => 7, 'keyId' => 8, 'value' => 'needle', 'variant' => ''],
            $this->createParameter(),
            null
        );
    }

    public function testUnresolvableIdentifiersThrowNotFoundAndDoNotFallBackToThePair(): void
    {
        $this->expectException(NotFoundException::class);

        $this->getValue(
            ['groupId' => 7, 'keyId' => 8, 'value' => 'needle', ...self::IDENTIFIERS],
            $this->createParameter(),
            new InvalidArgumentException('does not exist')
        );
    }

    public function testIdentifierOnlyValueWithoutAClassIdIsMissingItsPair(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getValue(['value' => 'needle', ...self::IDENTIFIERS], new FilterParameter(), null);
    }

    private function createParameter(): FilterParameter
    {
        $parameter = new FilterParameter();
        $parameter->setClassId(self::CLASS_ID);

        return $parameter;
    }

    private function getValue(
        array $raw,
        FilterParameter $parameter,
        ClassificationStoreConfig|InvalidArgumentException|null $resolved
    ): ClassificationStoreFilterValue {
        $service = $this->makeEmpty(ColumnReferenceServiceInterface::class, [
            'resolve' => function () use ($resolved): ?ClassificationStoreConfig {
                if ($resolved instanceof InvalidArgumentException) {
                    throw $resolved;
                }

                return $resolved;
            },
        ]);

        return (new ClassificationStoreFilterValueResolver($service))->resolve($raw, $parameter, 'cs');
    }
}
