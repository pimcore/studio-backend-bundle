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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * The identifier is assigned by the classification store, so a payload carrying one is refused rather
 * than silently ignored the way an unrecognised property is.
 *
 * @internal
 */
final class IdentifierInputRejectionTest extends Unit
{
    private const string IDENTIFIER = '01K9T0ZQ0VX8W4S1R7J3A2B5C6';

    private const string NAME = 'name';

    private const string STORE_ID = 'storeId';

    /**
     * @dataProvider payloadProvider
     *
     * @param class-string $class
     * @param array<string, mixed> $arguments
     */
    public function testAPayloadWithoutAnIdentifierStillConstructs(string $class, array $arguments): void
    {
        $this->assertInstanceOf($class, new $class(...$arguments));
    }

    /**
     * @dataProvider payloadProvider
     *
     * @param class-string $class
     * @param array<string, mixed> $arguments
     */
    public function testAPayloadCarryingAnIdentifierIsRefused(string $class, array $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);

        new $class(...[...$arguments, 'identifier' => self::IDENTIFIER]);
    }

    /**
     * An explicit null is a supplied value, not an omission: the request asked to set the identifier
     * and has to be told that it cannot, rather than being answered as though it had not asked.
     *
     * @dataProvider payloadProvider
     *
     * @param class-string $class
     * @param array<string, mixed> $arguments
     */
    public function testAPayloadCarryingAnExplicitlyNullIdentifierIsRefused(string $class, array $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);

        new $class(...[...$arguments, 'identifier' => null]);
    }

    /**
     * @dataProvider payloadProvider
     *
     * @param class-string $class
     * @param array<string, mixed> $arguments
     */
    public function testAPayloadCarryingAnIdentifierOfAnotherTypeIsRefused(string $class, array $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);

        new $class(...[...$arguments, 'identifier' => 0]);
    }

    /**
     * @return array<string, array{0: class-string, 1: array<string, mixed>}>
     */
    public static function payloadProvider(): array
    {
        return [
            'store create' => [StoreCreate::class, [self::NAME => 'My Store']],
            'store update' => [StoreUpdate::class, [self::NAME => 'My Store']],
            'collection create' => [CollectionCreate::class, [self::NAME => 'My Collection', self::STORE_ID => 1]],
            'collection update' => [CollectionUpdate::class, [self::NAME => 'My Collection']],
            'group create' => [GroupCreate::class, [self::NAME => 'My Group', self::STORE_ID => 1]],
            'group update' => [GroupUpdate::class, [self::NAME => 'My Group']],
            'key create' => [KeyCreate::class, [self::NAME => 'My Key', self::STORE_ID => 1]],
            'key update' => [KeyUpdate::class, [self::NAME => 'My Key']],
        ];
    }
}
