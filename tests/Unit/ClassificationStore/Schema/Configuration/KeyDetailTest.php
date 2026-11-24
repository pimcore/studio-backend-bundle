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
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyGroupRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use function array_map;

/**
 * @internal
 */
final class KeyDetailTest extends Unit
{
    private const string PROPERTY_LOCALIZED = 'localized';

    public function testTitleIsOptionalAndNullable(): void
    {
        $this->assertNull((new KeyDetail(42, 'weight', 1, 'input', true))->getTitle());
        $this->assertSame(
            'Weight',
            (new KeyDetail(42, 'weight', 1, 'input', true, null, [], null, null, 'Weight'))->getTitle()
        );

        $attributes = (new ReflectionClass(KeyDetail::class))->getAttributes(Schema::class);

        $this->assertSame(
            [
                'id', 'name', 'storeId', 'type', 'enabled', 'description',
                'definition', 'creationDate', 'modificationDate',
            ],
            $attributes[0]->getArguments()['required']
        );
    }

    /**
     * The flag is documented as a nullable boolean, which is what makes the third state a state in
     * the generated schema rather than a missing value a client reads as false.
     *
     * @dataProvider localizedSchemaProvider
     */
    public function testTheLocalizationFlagIsDocumentedAsANullableBoolean(string $class): void
    {
        $property = $this->getLocalizedProperty($class);

        $this->assertSame('boolean', $property->getArguments()['type']);
        $this->assertTrue($property->getArguments()['nullable']);
        $this->assertStringContainsString('class definition field', $property->getArguments()['description']);
    }

    /**
     * Every schema the flag joins, so one of them growing a differently shaped property is a
     * failure here rather than a difference a client finds in the generated document.
     *
     * @return array<string, array{0: class-string}>
     */
    public static function localizedSchemaProvider(): array
    {
        return [
            'the key detail' => [KeyDetail::class],
            'the key update' => [KeyUpdate::class],
            'the key group relation detail' => [KeyGroupRelationDetail::class],
            'the key layout' => [KeyLayout::class],
            'the store wide key group relation' => [KeyGroupRelation::class],
        ];
    }

    /**
     * @param class-string $class
     */
    private function getLocalizedProperty(string $class): ReflectionAttribute
    {
        foreach ((new ReflectionClass($class))->getConstructor()->getParameters() as $parameter) {
            if ($parameter->getName() !== self::PROPERTY_LOCALIZED) {
                continue;
            }

            $attributes = $parameter->getAttributes(Property::class);
            $this->assertCount(1, $attributes, $class . ' documents `localized` once.');

            return $attributes[0];
        }

        $this->fail($class . ' carries no `localized` property.');
    }

    /**
     * The options, their reason and the unit vocabulary are appended as optional members and join no
     * required list, so a client reading a key published before them is not told to expect them.
     */
    public function testTheResolvedMembersAreAppendedOptionalAndNullable(): void
    {
        $detail = new KeyDetail(42, 'weight', 1, 'input', true);

        $this->assertNull($detail->getOptions());
        $this->assertNull($detail->getOptionsUnavailableReason());
        $this->assertNull($detail->getUnits());

        $parameters = array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            (new ReflectionMethod(KeyDetail::class, '__construct'))->getParameters()
        );

        $this->assertSame(
            [
                'id', 'name', 'storeId', 'type', 'enabled', 'description', 'definition', 'creationDate',
                'modificationDate', 'title', 'identifier', 'localized', 'customMetadata',
                'options', 'optionsUnavailableReason', 'units',
            ],
            $parameters
        );

        $attributes = (new ReflectionClass(KeyDetail::class))->getAttributes(Schema::class);

        $this->assertSame(
            ['id', 'name', 'storeId', 'type', 'enabled', 'description', 'definition', 'creationDate', 'modificationDate'],
            $attributes[0]->getArguments()['required']
        );
    }
}
