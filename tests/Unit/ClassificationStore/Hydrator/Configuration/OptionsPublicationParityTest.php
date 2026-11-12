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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Hydrator\Configuration;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\Helper\OptionsProviderResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyGroupRelationHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyOptionsResolver;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\SelectOptionHydrator;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * `options` and `units` mean one thing wherever a key's configuration is read: the same key resolved
 * through the real resolver has to publish the same vocabulary on the key detail and on the key group
 * assignment detail. Neither surface is the authority and neither may be enriched alone - a hydrator
 * that read the assignment rather than the key would still pass every per-surface test.
 *
 * @internal
 */
final class OptionsPublicationParityTest extends Unit
{
    private const int KEY_ID = 42;

    private const int GROUP_ID = 7;

    private const array STORED_OPTIONS = [['key' => 'B', 'value' => 'b'], ['key' => 'A', 'value' => 'a']];

    public function testASelectKeyPublishesTheSameOptionsOnBothSurfaces(): void
    {
        $keyConfig = $this->keyConfig('select', '{"fieldtype":"select","options":"stored"}');
        $resolver = $this->resolver();

        $keyDetail = (new KeyHydrator(new InMemoryKeyLocalizationResolver(), $resolver))
            ->hydrateKeyDetail($keyConfig);
        $relationDetail = (new KeyGroupRelationHydrator($resolver))->hydrateKeyGroupRelationDetail(
            $this->relation($keyConfig),
            $keyConfig->getName(),
            $keyConfig->getDescription(),
            'My Group',
            null,
            $keyConfig,
        );

        // the published list is the key's own vocabulary, so the comparison cannot pass on two nulls
        $this->assertCount(2, (array) $keyDetail->getOptions());
        $this->assertEquals($keyDetail->getOptions(), $relationDetail->getOptions());
        $this->assertSame(
            $keyDetail->getOptionsUnavailableReason(),
            $relationDetail->getOptionsUnavailableReason()
        );
    }

    public function testAUnitCarryingKeyPublishesTheSameUnitsOnBothSurfaces(): void
    {
        $keyConfig = $this->keyConfig('quantityValue', '{"fieldtype":"quantityValue"}');
        $resolver = $this->resolver();

        $keyDetail = (new KeyHydrator(new InMemoryKeyLocalizationResolver(), $resolver))
            ->hydrateKeyDetail($keyConfig);
        $relationDetail = (new KeyGroupRelationHydrator($resolver))->hydrateKeyGroupRelationDetail(
            $this->relation($keyConfig),
            $keyConfig->getName(),
            $keyConfig->getDescription(),
            'My Group',
            null,
            $keyConfig,
        );

        $this->assertSame('kg', $keyDetail->getUnits()?->getDefaultUnit());
        $this->assertEquals($keyDetail->getUnits(), $relationDetail->getUnits());
        $this->assertSame($keyConfig->getType(), $relationDetail->getKeyType());
    }

    /**
     * The real resolver, with the two collaborators it cannot reach without a booted container: the
     * definition is rebuilt from the blob exactly as core rebuilds it, and no provider is named.
     */
    private function resolver(): KeyOptionsResolver
    {
        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getFieldDefinitionFromJson')->willReturnCallback(
            static function (array $blob, string $type): ?Data {
                if ($type === 'quantityValue') {
                    $definition = new QuantityValue();
                    $definition->setName('weight');
                    $definition->setDefaultUnit('kg');
                    $definition->setValidUnits(['kg', 'g']);

                    return $definition;
                }

                $definition = new Select();
                $definition->setName('weight');
                $definition->setOptions(self::STORED_OPTIONS);

                return $definition;
            }
        );

        return new KeyOptionsResolver(
            $serviceResolver,
            $this->createMock(OptionsProviderResolverInterface::class),
            new SelectOptionHydrator()
        );
    }

    private function keyConfig(string $type, string $definition): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId(self::KEY_ID);
        $keyConfig->setName('weight');
        $keyConfig->setStoreId(1);
        $keyConfig->setType($type);
        $keyConfig->setDefinition($definition);

        return $keyConfig;
    }

    private function relation(KeyConfig $keyConfig): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setType($keyConfig->getType());
        $relation->setDefinition($keyConfig->getDefinition());

        return $relation;
    }
}
