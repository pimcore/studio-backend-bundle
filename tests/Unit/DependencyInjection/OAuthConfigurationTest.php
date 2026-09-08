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
use ReflectionMethod;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * The embedded OAuth server has no meaningful identity without an issuer: it is
 * stamped on tokens (`iss`) and is the base for protected-resource URIs, and it
 * cannot be derived per request. Leaving it unset while enabling OAuth registers
 * no protected resource at all, so every OAuth request would 401 with nothing
 * pointing at the cause — hence it is rejected at container-compile time.
 *
 * The URL *shape* is documented rather than validated, matching how the rest of
 * this configuration treats operator-supplied URLs (e.g. client redirect_uris).
 *
 * @internal
 */
final class OAuthConfigurationTest extends Unit
{
    public function testEnablingOAuthWithoutAnIssuerIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/issuer/');

        $this->process(['enabled' => true]);
    }

    public function testEnablingOAuthWithAnIssuerIsAccepted(): void
    {
        $processed = $this->process(['enabled' => true, 'issuer' => 'https://studio.example.com']);

        $this->assertSame('https://studio.example.com', $processed['oauth']['issuer']);
    }

    /**
     * An `%env(...)%` placeholder resolves at container runtime, so the documented
     * env-driven form must pass configuration processing untouched.
     */
    public function testAnEnvPlaceholderIssuerIsAccepted(): void
    {
        $processed = $this->process(['enabled' => true, 'issuer' => '%env(PIMCORE_OAUTH_ISSUER)%']);

        $this->assertSame('%env(PIMCORE_OAUTH_ISSUER)%', $processed['oauth']['issuer']);
    }

    public function testDisabledOAuthDoesNotRequireAnIssuer(): void
    {
        $processed = $this->process(['enabled' => false]);

        $this->assertNull($processed['oauth']['issuer']);
    }

    /**
     * @param array<string, mixed> $oauth
     *
     * @return array<string, mixed>
     */
    private function process(array $oauth): array
    {
        $root = (new TreeBuilder('pimcore_studio_backend'))->getRootNode();
        (new ReflectionMethod(Configuration::class, 'addOAuthNode'))
            ->invoke(new Configuration(), $root);

        return (new Processor())->process(
            $root->getNode(true),
            [['oauth' => $oauth]]
        );
    }
}
