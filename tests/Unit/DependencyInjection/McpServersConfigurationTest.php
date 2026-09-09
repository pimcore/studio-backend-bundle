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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use ReflectionMethod;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

/**
 * The file-based (symfony-config) MCP server tree must accept the same
 * name-based, two-capability access shape the settings-store path uses, so a
 * YAML-configured server does not blow up at container-compile time.
 *
 * @internal
 */
final class McpServersConfigurationTest extends Unit
{
    /**
     * @param array<string, mixed> $servers
     *
     * @return array<string, mixed>
     */
    private function process(array $servers): array
    {
        $root = (new TreeBuilder('pimcore_studio_backend'))->getRootNode();
        (new ReflectionMethod(Configuration::class, 'addMcpServersConfigurationNode'))
            ->invoke(new Configuration(), $root);

        return (new Processor())->process(
            $root->getNode(true),
            [[Configuration::MCP_SERVERS_NODE => $servers]]
        );
    }

    public function testOwnerIsAUsernameString(): void
    {
        $processed = $this->process([
            'cars' => ['access' => ['owner' => 'john.doe']],
        ]);

        $this->assertSame('john.doe', $processed[Configuration::MCP_SERVERS_NODE]['cars']['access']['owner']);
    }

    public function testSharedUsersAndRolesAcceptTheCapabilityGrid(): void
    {
        $processed = $this->process([
            'cars' => [
                'access' => [
                    'owner' => 'john.doe',
                    'share_global' => true,
                    'shared_users' => [
                        ['name' => 'alice', 'can_read' => true, 'can_access' => true, 'can_edit' => false],
                        'bob',
                    ],
                    'shared_roles' => [
                        ['name' => 'editors', 'can_edit' => true],
                    ],
                ],
            ],
        ]);

        $access = $processed[Configuration::MCP_SERVERS_NODE]['cars']['access'];

        $this->assertSame('john.doe', $access['owner']);
        $this->assertTrue($access['share_global']);
        // assertEquals, not assertSame: Symfony appends defaulted keys after the
        // provided ones, so the associative key order is not fixed (list order is).
        // A grant that only names someone still lets them see the server, which is why
        // `can_read` defaults to true rather than false like the other two.
        $this->assertEquals([
            ['name' => 'alice', 'can_read' => true, 'can_access' => true, 'can_edit' => false],
            ['name' => 'bob', 'can_read' => true, 'can_access' => false, 'can_edit' => false],
        ], $access['shared_users']);
        $this->assertEquals([
            ['name' => 'editors', 'can_read' => true, 'can_access' => false, 'can_edit' => true],
        ], $access['shared_roles']);
    }

    public function testProcessedAccessRoundTripsThroughMcpServerAccess(): void
    {
        $processed = $this->process([
            'cars' => [
                'access' => [
                    'owner' => 'john.doe',
                    'shared_users' => [['name' => 'alice', 'can_access' => true, 'can_edit' => true]],
                ],
            ],
        ]);

        $access = McpServerAccess::fromArray($processed[Configuration::MCP_SERVERS_NODE]['cars']['access']);

        $this->assertSame('john.doe', $access->owner);
        $this->assertEquals(
            [new McpServerAccessEntry('alice', canAccess: true, canEdit: true)],
            $access->sharedUsers
        );
    }
}
