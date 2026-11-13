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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Version\Repository;

use const E_USER_DEPRECATED;
use Codeception\Test\Unit;
use Error;
use Pimcore\Bundle\StaticResolverBundle\Models\Version\VersionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Context\McpCallContext;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\MappedParameter\UpdateVersionParameter;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepository;
use Pimcore\Model\Version;
use ReflectionClass;
use function error_reporting;
use function restore_error_handler;
use function set_error_handler;

final class VersionRepositoryTest extends Unit
{
    private McpCallContext $mcpCallContext;

    private VersionRepository $repository;

    protected function _before(): void
    {
        $this->mcpCallContext = new McpCallContext();
        $this->repository = new VersionRepository(
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(VersionResolverInterface::class),
            $this->mcpCallContext,
        );
    }

    public function testOutsideMcpTheCoauthorFieldsAreStillAppliedWithADeprecation(): void
    {
        $version = $this->version('old-type', 'old');
        $deprecations = [];

        $this->withDeprecations($deprecations, function () use ($version): void {
            $this->update($version, new UpdateVersionParameter(coauthorType: 'agent', coauthor: 'someone'));
        });

        $this->assertSame('agent', $version->getCoauthorType());
        $this->assertSame('someone', $version->getCoauthor());
        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('2027.1', $deprecations[0]);
        $this->assertStringContainsString('platform-version/issues/814', $deprecations[0]);
    }

    public function testOutsideMcpAnEmptyStringStillClearsTheCoauthor(): void
    {
        $version = $this->version('agent', 'someone');
        $deprecations = [];

        $this->withDeprecations($deprecations, function () use ($version): void {
            $this->update($version, new UpdateVersionParameter(coauthorType: '', coauthor: ''));
        });

        $this->assertNull($version->getCoauthorType());
        $this->assertNull($version->getCoauthor());
    }

    public function testInsideMcpTheCoauthorFieldsAreIgnored(): void
    {
        $version = $this->version('mcp-server', 'pme-test');
        $deprecations = [];

        $this->withDeprecations($deprecations, function () use ($version): void {
            $this->mcpCallContext->run(fn () => $this->update(
                $version,
                new UpdateVersionParameter(note: 'a note', coauthorType: '', coauthor: 'forged')
            ));
        });

        $this->assertSame('mcp-server', $version->getCoauthorType());
        $this->assertSame('pme-test', $version->getCoauthor());
        $this->assertSame('a note', $version->getNote());
        $this->assertSame([], $deprecations);
    }

    public function testWithoutCoauthorFieldsThereIsNoDeprecation(): void
    {
        $version = $this->version('agent', 'someone');
        $deprecations = [];

        $this->withDeprecations($deprecations, function () use ($version): void {
            $this->update($version, new UpdateVersionParameter(note: 'n'));
        });

        $this->assertSame([], $deprecations);
        $this->assertSame('agent', $version->getCoauthorType());
    }

    /**
     * Version is final and needs the kernel container in its constructor and in save(): build it without
     * the constructor and let the persisting save() fail, the fields are applied before it.
     */
    private function version(string $type, string $coauthor): Version
    {
        $version = (new ReflectionClass(Version::class))->newInstanceWithoutConstructor();
        $version->setCoauthorType($type);
        $version->setCoauthor($coauthor);

        return $version;
    }

    private function update(Version $version, UpdateVersionParameter $parameter): void
    {
        try {
            $this->repository->updateVersion($version, $parameter);
        } catch (Error) {
            // save() needs the kernel container; the coauthor fields are already applied by then
        }
    }

    /**
     * @param list<string> $messages
     */
    private function withDeprecations(array &$messages, callable $callback): void
    {
        $previousLevel = error_reporting(-1);
        set_error_handler(static function (int $errno, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $callback();
        } finally {
            restore_error_handler();
            error_reporting($previousLevel);
        }
    }
}
