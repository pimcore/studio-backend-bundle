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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use ReflectionClass;

/**
 * The key's `enabled` flag is changed by the restore endpoint and the delete only, so neither write
 * payload gains a way to disable a key.
 *
 * @internal
 */
final class KeyEnabledNotWritableTest extends Unit
{
    /**
     * @dataProvider payloadProvider
     */
    public function testThePayloadDeclaresNoEnabledMember(string $schema): void
    {
        $this->assertFalse((new ReflectionClass($schema))->hasProperty('enabled'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function payloadProvider(): array
    {
        return [
            'update' => [KeyUpdate::class],
            'create' => [KeyCreate::class],
        ];
    }
}
