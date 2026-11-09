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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
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
     * A JSON list is a PHP array too, so `is_array()` alone accepted `{"customMetadata": ["erp"]}`
     * and stored a list under a member whose contract is an object.
     *
     * @dataProvider schemaProvider
     *
     * @param class-string<CollectionUpdate|GroupUpdate|SubGroupUpdate> $class
     */
    public function testANonEmptyListIsRefused(string $class): void
    {
        foreach ([['erp'], ['erp', 'pim'], [0 => 'erp', 1 => 'pim']] as $list) {
            try {
                new $class(name: self::NAME, customMetadata: $list);
                $this->fail('Expected the list shaped customMetadata to be refused.');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('customMetadata', $e->getMessage());
            }
        }
    }

    /**
     * The `{}` and `[]` distinction, decided deliberately: `json_decode` hands both back as the
     * same empty array, so the empty object the contract allows stays accepted and counts as
     * supplied - which is how a client empties the pairs without clearing the column.
     *
     * @dataProvider schemaProvider
     *
     * @param class-string<CollectionUpdate|GroupUpdate|SubGroupUpdate> $class
     */
    public function testAnEmptyObjectIsAcceptedAndSupplied(string $class): void
    {
        $update = new $class(name: self::NAME, customMetadata: []);

        $this->assertTrue($update->hasCustomMetadata());
        $this->assertSame([], $update->getCustomMetadata());
    }

    /**
     * The fourth update schema asks the same question of the same member, and its own `?array`
     * type stops a scalar without saying anything about a list.
     */
    public function testAKeyUpdateRefusesANonEmptyListAndAcceptsAnEmptyObject(): void
    {
        $empty = new KeyUpdate(name: self::NAME, customMetadata: []);

        $this->assertTrue($empty->isCustomMetadataSupplied());
        $this->assertSame([], $empty->getCustomMetadata());

        $this->expectException(InvalidArgumentException::class);

        new KeyUpdate(name: self::NAME, customMetadata: ['erp']);
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
