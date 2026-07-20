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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Server;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\PendingAuthorizationStore;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class PendingAuthorizationStoreTest extends Unit
{
    public function testStoreGetRemoveRoundTrip(): void
    {
        $store = new PendingAuthorizationStore(new ArrayAdapter(), 600);
        $params = ['client_id' => 'studio-mcp', 'scope' => 'mcp:read', 'state' => 'xyz'];

        $store->store('abc', $params);
        $this->assertSame($params, $store->get('abc'));

        $store->remove('abc');
        $this->assertNull($store->get('abc'));
    }

    public function testUnknownIdReturnsNull(): void
    {
        $this->assertNull((new PendingAuthorizationStore(new ArrayAdapter(), 600))->get('missing'));
    }
}
