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
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\PimcoreStudioBackendExtension;
use Pimcore\Bundle\StudioBackendBundle\OAuth\EventSubscriber\OAuthEndpointGuardSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * OAuthEndpointGuardSubscriber listens to every request, so whatever it is constructed with
 * is resolved on every request. An issuer taken from an unset environment variable would
 * then fail every page of the installation, not only OAuth, although nothing needs the
 * issuer while OAuth is off.
 *
 * @internal
 */
final class OAuthEndpointGuardWiringTest extends Unit
{
    private const string ISSUER = 'https://pimcore.example.com';

    public function testTheIssuerIsNotWiredWhileOAuthIsDisabled(): void
    {
        $arguments = $this->guardArguments(['enabled' => false, 'issuer' => self::ISSUER]);

        $this->assertFalse($arguments['$enabled']);
        $this->assertNull($arguments['$issuer']);
    }

    public function testTheIssuerIsWiredWhileOAuthIsEnabled(): void
    {
        $arguments = $this->guardArguments([
            'enabled' => true,
            'issuer' => self::ISSUER,
            'keys' => ['private_key' => 'a', 'public_key' => 'b', 'encryption_key' => 'c'],
        ]);

        $this->assertTrue($arguments['$enabled']);
        $this->assertSame(self::ISSUER, $arguments['$issuer']);
    }

    /**
     * @param array<string, mixed> $oauth
     *
     * @return array<int|string, mixed>
     */
    private function guardArguments(array $oauth): array
    {
        // The bundle's shipped defaults, which Pimcore loads ahead of any project config.
        $defaults = Yaml::parseFile(__DIR__ . '/../../../config/pimcore/config.yaml')['pimcore_studio_backend'];

        $container = new ContainerBuilder();
        (new PimcoreStudioBackendExtension())->load([$defaults, ['oauth' => $oauth]], $container);

        return $container->getDefinition(OAuthEndpointGuardSubscriber::class)->getArguments();
    }
}
