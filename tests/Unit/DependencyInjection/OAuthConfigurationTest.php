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
 * cannot be derived per request. So enabling OAuth without one — or with anything
 * other than a bare http(s) origin — is rejected at container-compile time rather
 * than failing silently at runtime.
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

    /**
     * @dataProvider malformedIssuers
     */
    public function testAMalformedIssuerIsRejected(string $issuer): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/issuer/');

        $this->process(['enabled' => true, 'issuer' => $issuer]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedIssuers(): array
    {
        return [
            'empty' => [''],
            'no scheme or host' => ['not-an-absolute-url'],
            'non-http(s) scheme' => ['ftp://pimcore.example.com'],
            'with a path' => ['https://pimcore.example.com/base'],
            'with a trailing slash' => ['https://pimcore.example.com/'],
            'with a query' => ['https://pimcore.example.com?tenant=a'],
            'with a fragment' => ['https://pimcore.example.com#frag'],
            'with userinfo' => ['https://user:pass@pimcore.example.com'],
        ];
    }

    /**
     * An `%env(...)%` placeholder resolves at container runtime, so the shape cannot
     * (and must not) be validated at config-compile time — the documented env-driven
     * form must be accepted.
     */
    public function testAnEnvPlaceholderIssuerIsAccepted(): void
    {
        $processed = $this->process(['enabled' => true, 'issuer' => '%env(PIMCORE_OAUTH_ISSUER)%']);

        $this->assertSame('%env(PIMCORE_OAUTH_ISSUER)%', $processed['oauth']['issuer']);
    }

    public function testAnAbsoluteHttpsOriginIsAccepted(): void
    {
        $processed = $this->process(['enabled' => true, 'issuer' => 'https://studio.example.com:8443']);

        $this->assertSame('https://studio.example.com:8443', $processed['oauth']['issuer']);
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
