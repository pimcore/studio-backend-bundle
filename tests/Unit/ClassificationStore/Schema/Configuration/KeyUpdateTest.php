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
use OpenApi\Attributes\Schema;
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
}
