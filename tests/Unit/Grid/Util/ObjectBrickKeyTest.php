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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Util;

use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ObjectBrickKey;

/**
 * @internal
 */
final class ObjectBrickKeyTest extends Unit
{
    public function testFromColumnKeySplitsFieldBrickAndAttribute(): void
    {
        $key = ObjectBrickKey::fromColumnKey('attributes.Engine.power');

        self::assertNotNull($key);
        self::assertSame('attributes', $key->getField());
        self::assertSame('Engine', $key->getBrickName());
        self::assertSame('power', $key->getAttribute());
    }

    /**
     * @dataProvider invalidColumnKeyProvider
     */
    #[DataProvider('invalidColumnKeyProvider')]
    public function testFromColumnKeyReturnsNullForOtherFormats(string $columnKey): void
    {
        self::assertNull(ObjectBrickKey::fromColumnKey($columnKey));
    }

    public static function invalidColumnKeyProvider(): array
    {
        return [
            'class field' => ['price'],
            'two parts' => ['attributes.Engine'],
            'four parts' => ['attributes.Engine.power.value'],
        ];
    }
}
