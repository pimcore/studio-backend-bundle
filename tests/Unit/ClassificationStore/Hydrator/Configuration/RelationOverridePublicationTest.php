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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyOptionsResolver;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\SelectOptionHydrator;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use function array_map;
use function json_encode;

/**
 * The options and units an assignment publishes are the ones its own override leaves, not the key's.
 *
 * @internal
 */
final class RelationOverridePublicationTest extends Unit
{
    private const array STORED_OPTIONS = [['key' => 'B', 'value' => 'b'], ['key' => 'A', 'value' => 'a']];

    public function testAnAssignmentPublishesTheOptionsAndUnitsItNarrowedTo(): void
    {
        $hydrator = new KeyGroupRelationHydrator($this->resolver());

        $select = $hydrator->hydrateKeyGroupRelationDetail(
            $this->relation('select', ['options' => self::STORED_OPTIONS], ['options' => [self::STORED_OPTIONS[1]]]),
            keyConfig: $this->keyConfig()
        );
        $quantity = $hydrator->hydrateKeyGroupRelationDetail(
            $this->relation(
                'quantityValue',
                ['defaultUnit' => 'm', 'validUnits' => ['m', 'kg']],
                ['validUnits' => ['kg'], 'defaultUnit' => 'kg']
            ),
            keyConfig: $this->keyConfig()
        );

        $this->assertSame(
            ['A'],
            array_map(static fn (SelectOption $option): string => $option->getKey(), (array) $select->getOptions())
        );
        $this->assertSame('kg', $quantity->getUnits()?->getDefaultUnit());
        $this->assertSame(['kg'], $quantity->getUnits()?->getValidUnits());
    }

    private function keyConfig(): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setType('input');

        return $keyConfig;
    }

    private function resolver(): KeyOptionsResolver
    {
        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getFieldDefinitionFromJson')->willReturnCallback(
            static function (array $blob, string $type): ?Data {
                if ($type === 'quantityValue') {
                    $definition = new QuantityValue();
                    $definition->setDefaultUnit($blob['defaultUnit'] ?? null);
                    $definition->setValidUnits($blob['validUnits'] ?? []);

                    return $definition;
                }

                $definition = new Select();
                $definition->setOptions($blob['options'] ?? []);

                return $definition;
            }
        );

        return new KeyOptionsResolver(
            $serviceResolver,
            $this->createMock(OptionsProviderResolverInterface::class),
            new SelectOptionHydrator()
        );
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $override
     */
    private function relation(string $type, array $definition, array $override): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(1);
        $relation->setGroupId(1);
        $relation->setType($type);
        $relation->setDefinition((string) json_encode($definition));
        $relation->setDefinitionOverride($override);

        return $relation;
    }
}
