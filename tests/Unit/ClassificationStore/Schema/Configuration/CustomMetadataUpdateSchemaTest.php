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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * An update that omits `customMetadata` leaves the stored pairs alone - which is what every caller
 * that sends a name and a description only relies on - and the member itself is checked for its
 * shape and for nothing else.
 *
 * @internal
 */
final class CustomMetadataUpdateSchemaTest extends Unit
{
    private const string NAME = 'Measurements';

    /**
     * @dataProvider schemaProvider
     *
     * @param class-string<CollectionUpdate|GroupUpdate|SubGroupUpdate> $class
     */
    public function testAnOmittedMemberIsNotSupplied(string $class): void
    {
        $this->assertFalse((new $class(name: self::NAME, description: 'Description'))->hasCustomMetadata());
    }

    /**
     * @dataProvider schemaProvider
     *
     * @param class-string<CollectionUpdate|GroupUpdate|SubGroupUpdate> $class
     */
    public function testAnExplicitNullIsSuppliedAndClears(string $class): void
    {
        $update = new $class(name: self::NAME, customMetadata: null);

        $this->assertTrue($update->hasCustomMetadata());
        $this->assertNull($update->getCustomMetadata());
    }

    /**
     * Every member name is accepted and kept as it came: none is reserved, none is a pattern, and a
     * value is not restricted to a string.
     *
     * @dataProvider schemaProvider
     *
     * @param class-string<CollectionUpdate|GroupUpdate|SubGroupUpdate> $class
     */
    public function testEveryMemberNameAndValueIsAcceptedUnchanged(string $class): void
    {
        $pairs = [
            'indexed' => false,
            'title.de' => 'Gewicht',
            'name' => 'x',
            'definition' => ['a' => 1],
            'subGroup' => 5,
            'list' => ['a', 'b'],
        ];

        $update = new $class(name: self::NAME, customMetadata: $pairs);

        $this->assertTrue($update->hasCustomMetadata());
        $this->assertSame($pairs, $update->getCustomMetadata());
    }

    /**
     * @dataProvider schemaProvider
     *
     * @param class-string<CollectionUpdate|GroupUpdate|SubGroupUpdate> $class
     */
    public function testAMemberThatIsNotAnObjectIsRefused(string $class): void
    {
        $this->expectException(InvalidArgumentException::class);

        new $class(name: self::NAME, customMetadata: 5);
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function schemaProvider(): array
    {
        return [
            CollectionUpdate::class => [CollectionUpdate::class],
            GroupUpdate::class => [GroupUpdate::class],
            SubGroupUpdate::class => [SubGroupUpdate::class],
        ];
    }
}
