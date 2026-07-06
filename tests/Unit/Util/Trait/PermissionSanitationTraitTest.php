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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Util\Trait;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PermissionSanitationTrait;
use Pimcore\Model\User\Permission\Definition;

/**
 * @internal
 */
final class PermissionSanitationTraitTest extends Unit
{
    public function testEmptyArrayReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->createTraitHelper()->sanitize([]));
    }

    public function testEmptyStringsAreRemoved(): void
    {
        $result = $this->createTraitHelper()->sanitize(['assets', '', 'documents', '']);

        $this->assertSame(['assets', 'documents'], $result);
    }

    public function testAllPermissionsPassThroughWithoutAvailableList(): void
    {
        $permissions = ['assets', 'documents', 'objects'];

        $this->assertSame($permissions, $this->createTraitHelper()->sanitize($permissions));
    }

    public function testEmptyAvailableListDoesNotFilterPermissions(): void
    {
        $permissions = ['assets', 'documents'];

        $this->assertSame($permissions, $this->createTraitHelper()->sanitize($permissions, []));
    }

    public function testUnknownPermissionsAreFilteredOut(): void
    {
        $permissions = ['assets', 'unknown_permission', 'documents'];
        $available = $this->definitions('assets', 'documents', 'objects');

        $this->assertSame(['assets', 'documents'], $this->createTraitHelper()->sanitize($permissions, $available));
    }

    public function testEmptyStringsAndUnavailablePermissionsAreRemovedTogether(): void
    {
        $permissions = ['assets', '', 'unknown_permission', 'documents'];
        $available = $this->definitions('assets', 'documents', 'objects');

        $this->assertSame(['assets', 'documents'], $this->createTraitHelper()->sanitize($permissions, $available));
    }

    public function testResultIsSequentiallyIndexedAfterFiltering(): void
    {
        // Regression test: array_filter() preserves original keys, so a filtered array like
        // ['assets'(0), 'unknown'(1), 'documents'(2)] → [0 => 'assets', 2 => 'documents']
        // which JSON-encodes as an object {} instead of an array [].
        // array_values() must re-index to [0 => 'assets', 1 => 'documents'].
        $permissions = ['assets', 'unknown_permission', 'documents'];
        $available = $this->definitions('assets', 'documents');

        $result = $this->createTraitHelper()->sanitize($permissions, $available);

        $this->assertSame([0, 1], array_keys($result));
        $this->assertSame(['assets', 'documents'], $result);
    }

    public function testAllPermissionsFilteredWhenNoneMatchAvailableList(): void
    {
        $permissions = ['unknown_a', 'unknown_b'];
        $available = $this->definitions('assets', 'documents');

        $this->assertSame([], $this->createTraitHelper()->sanitize($permissions, $available));
    }

    public function testDefinitionWithNullKeyMatchesNothingAndDoesNotError(): void
    {
        $permissions = ['assets', 'documents'];
        // A Definition without a key returns null from getKey(); it must not match any permission.
        $available = [new Definition(), (new Definition())->setKey('assets')];

        $this->assertSame(['assets'], $this->createTraitHelper()->sanitize($permissions, $available));
    }

    /**
     * @return Definition[]
     */
    private function definitions(string ...$keys): array
    {
        return array_map(static fn (string $key) => (new Definition())->setKey($key), $keys);
    }

    private function createTraitHelper(): object
    {
        return new class {
            use PermissionSanitationTrait;

            /**
             * @param Definition[] $availablePermissions
             */
            public function sanitize(array $permissions, array $availablePermissions = []): array
            {
                return $this->sanitizePermissions($permissions, $availablePermissions);
            }
        };
    }
}
