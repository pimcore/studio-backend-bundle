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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Schema\Configuration;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use ReflectionClass;
use ReflectionParameter;
use function array_map;

/**
 * @internal
 */
final class KeyGroupRelationUpdateTest extends Unit
{
    public function testAnAbsentPropertyIsDistinguishableFromAnExplicitNull(): void
    {
        $absent = new KeyGroupRelationUpdate();

        $this->assertFalse($absent->hasSorter());
        $this->assertFalse($absent->hasMandatory());
        $this->assertFalse($absent->hasDefinitionOverride());
        $this->assertFalse($absent->hasSubGroup());
        $this->assertFalse($absent->hasMetadata());

        $cleared = new KeyGroupRelationUpdate(definitionOverride: null, subGroup: null, metadata: null);

        $this->assertTrue($cleared->hasDefinitionOverride());
        $this->assertTrue($cleared->hasSubGroup());
        $this->assertTrue($cleared->hasMetadata());
        $this->assertNull($cleared->getDefinitionOverride());
        $this->assertNull($cleared->getSubGroup());
        $this->assertNull($cleared->getMetadata());
    }

    public function testAValueIsReportedAsSupplied(): void
    {
        $parameters = new KeyGroupRelationUpdate(
            sorter: 5,
            mandatory: true,
            definitionOverride: ['defaultUnit' => 'kg'],
            subGroup: 'dimensions',
            metadata: ['source' => 'erp']
        );

        $this->assertSame(5, $parameters->getSorter());
        $this->assertTrue($parameters->isMandatory());
        $this->assertSame(['defaultUnit' => 'kg'], $parameters->getDefinitionOverride());
        $this->assertSame('dimensions', $parameters->getSubGroup());
        $this->assertSame(['source' => 'erp'], $parameters->getMetadata());
    }

    /**
     * @dataProvider nonNullableFieldProvider
     *
     * @param array<string, mixed> $payload
     */
    public function testExplicitNullOnANonNullableFieldIsRefused(array $payload): void
    {
        $this->expectException(InvalidArgumentException::class);

        new KeyGroupRelationUpdate(...$payload);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function nonNullableFieldProvider(): array
    {
        return [
            'sorter' => [['sorter' => null]],
            'mandatory' => [['mandatory' => null]],
        ];
    }

    public function testAValueOfTheWrongTypeIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new KeyGroupRelationUpdate(sorter: 'first');
    }

    public function testThePairIsNotAcceptedInTheBody(): void
    {
        $parameters = array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            (new ReflectionClass(KeyGroupRelationUpdate::class))->getConstructor()?->getParameters() ?? []
        );

        $this->assertSame(
            ['sorter', 'mandatory', 'definitionOverride', 'subGroup', 'metadata'],
            $parameters
        );
    }
}
