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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use ReflectionClass;

/**
 * @internal
 */
final class KeyUpdateTest extends Unit
{
    public function testTitleAndDescriptionAreNoLongerRequired(): void
    {
        $attributes = (new ReflectionClass(KeyUpdate::class))->getAttributes(Schema::class);

        $this->assertSame(['name', 'type', 'definition'], $attributes[0]->getArguments()['required']);
    }

    public function testNeitherTitleNorDescriptionCountsAsSuppliedWithoutAFlag(): void
    {
        $parameters = new KeyUpdate('weight');

        $this->assertFalse($parameters->isTitleSupplied());
        $this->assertFalse($parameters->isDescriptionSupplied());
    }

    /**
     * @dataProvider inferredPresenceProvider
     */
    public function testANonNullValueCountsAsSuppliedWithoutAFlag(?string $value, bool $expected): void
    {
        $title = new KeyUpdate(name: 'weight', title: $value);
        $description = new KeyUpdate(name: 'weight', description: $value);

        $this->assertSame($expected, $title->isTitleSupplied());
        $this->assertSame($expected, $description->isDescriptionSupplied());
    }

    /**
     * @return array<string, array{0: ?string, 1: bool}>
     */
    public static function inferredPresenceProvider(): array
    {
        return [
            'a filled value is supplied' => ['Mass', true],
            'an empty value is supplied' => ['', true],
            'null without a flag is an omission' => [null, false],
        ];
    }

    public function testWithDefinitionKeepsThePresenceFlags(): void
    {
        $parameters = (new KeyUpdate(name: 'weight', title: '', titleSupplied: true, descriptionSupplied: true))
            ->withDefinition(['fieldtype' => 'input']);

        $this->assertSame(['fieldtype' => 'input'], $parameters->getDefinition());
        $this->assertTrue($parameters->isTitleSupplied());
        $this->assertTrue($parameters->isDescriptionSupplied());
        $this->assertSame('', $parameters->getTitle());
    }

    /**
     * `localized` carries a presence marker of its own, the way the title and the description do:
     * null is a value a request can ask for here, so inference from the value alone cannot tell a
     * supplied null from an omitted member, and the repository would never write NULL at all.
     */
    public function testAnAbsentLocalizationFlagIsDistinguishableFromASuppliedNull(): void
    {
        $absent = new KeyUpdate('weight');
        $suppliedNull = new KeyUpdate(name: 'weight', localized: null, localizedSupplied: true);

        $this->assertFalse($absent->isLocalizedSupplied());
        $this->assertTrue($suppliedNull->isLocalizedSupplied());
        $this->assertNull($absent->getLocalized());
        $this->assertNull($suppliedNull->getLocalized());
    }

    /**
     * @dataProvider inferredLocalizationProvider
     */
    public function testANonNullLocalizationFlagCountsAsSuppliedWithoutAFlag(?bool $value, bool $expected): void
    {
        $parameters = new KeyUpdate(name: 'weight', localized: $value);

        $this->assertSame($expected, $parameters->isLocalizedSupplied());
        $this->assertSame($value, $parameters->getLocalized());
    }

    /**
     * @return array<string, array{0: ?bool, 1: bool}>
     */
    public static function inferredLocalizationProvider(): array
    {
        return [
            'true is supplied' => [true, true],
            'false is supplied' => [false, true],
            'null without a flag is an omission' => [null, false],
        ];
    }

    /**
     * The service hands on a new instance whenever a definition is present, so a supplied null
     * flag has to survive that copy or a request that sends `localized: null` together with a
     * definition would silently leave the stored flag alone.
     */
    public function testWithDefinitionKeepsASuppliedNullLocalizationFlag(): void
    {
        $parameters = (new KeyUpdate(name: 'weight', localized: null, localizedSupplied: true))
            ->withDefinition(['fieldtype' => 'input']);

        $this->assertTrue($parameters->isLocalizedSupplied());
        $this->assertNull($parameters->getLocalized());
    }

    /**
     * `variants` and `multiValue` are reserved members of the definition and nothing else: they are
     * carried by the definition the request sends and are not properties of this schema, so they
     * reach the stored blob verbatim and no validation of this schema can touch them.
     */
    public function testTheQualifiedValueDeclarationTravelsInTheDefinitionVerbatim(): void
    {
        $definition = [
            'fieldtype' => 'quantityValue',
            'variants' => ['metric', 'imperial'],
            'multiValue' => true,
        ];

        $parameters = new KeyUpdate(name: 'weight', definition: $definition);

        $this->assertSame($definition, $parameters->getDefinition());
        $this->assertSame($definition, $parameters->withDefinition($definition)->getDefinition());
    }

    /**
     * Neither member joined this schema's own properties, so a client that sends them inside the
     * definition - which is where they live - is not told to send them twice.
     */
    public function testNeitherMemberBecameAPropertyOfItsOwn(): void
    {
        $names = [];
        foreach ((new ReflectionClass(KeyUpdate::class))->getConstructor()->getParameters() as $parameter) {
            $names[] = $parameter->getName();
        }

        $this->assertNotContains('variants', $names);
        $this->assertNotContains('multiValue', $names);
    }

    /**
     * The reserved members are named in the `definition` description, the way `indexed` already is,
     * so the generated documentation says what they mean.
     */
    public function testTheDefinitionDescriptionNamesTheReservedMembers(): void
    {
        $description = $this->getDefinitionDescription(KeyUpdate::class);

        $this->assertStringContainsString('"variants"', $description);
        $this->assertStringContainsString('"multiValue"', $description);
        $this->assertStringContainsString('"indexed"', $description);
    }

    /**
     * The read side says the same, so a client reading a key and a client writing one are told the
     * same thing about the two members.
     */
    public function testTheKeyDetailDescriptionNamesTheSameReservedMembers(): void
    {
        $description = $this->getDefinitionDescription(KeyDetail::class);

        $this->assertStringContainsString('"variants"', $description);
        $this->assertStringContainsString('"multiValue"', $description);
        $this->assertStringContainsString('"indexed"', $description);
    }

    private function getDefinitionDescription(string $schema): string
    {
        $parameters = (new ReflectionClass($schema))->getConstructor()->getParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->getName() !== 'definition') {
                continue;
            }

            $attributes = $parameter->getAttributes(Property::class);

            return (string) $attributes[0]->getArguments()['description'];
        }

        $this->fail('The schema carries no definition property.');
    }
}
