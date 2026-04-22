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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mercure\Service;

use Codeception\Test\Unit;
use LogicException;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UrlService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class UrlServiceTest extends Unit
{
    public function testGetServerSideUrlWithCustomUrl(): void
    {
        $service = new UrlService('https://custom/mercure', null, new RequestStack());

        $this->assertSame('https://custom/mercure', $service->getServerSideUrl());
    }

    public function testGetServerSideUrlThrowsWhenNotConfigured(): void
    {
        $service = new UrlService(null, null, new RequestStack());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mercure server URL is not configured.');
        $service->getServerSideUrl();
    }

    public function testGetClientSideUrlWithCustomUrlReplacesPlaceholder(): void
    {
        $service = new UrlService(
            null,
            '<PIMCORE_SCHEMA_HOST>/custom-hub',
            $this->createRequestStackWithRequest('https://example.com'),
        );

        $this->assertSame('https://example.com/custom-hub', $service->getClientSideUrl());
    }

    public function testGetClientSideUrlWithDefault(): void
    {
        $service = new UrlService(
            null,
            null,
            $this->createRequestStackWithRequest('https://example.com')
        );

        $this->assertSame('https://example.com/hub', $service->getClientSideUrl());
    }

    public function testThrowsLogicExceptionWithoutRequest(): void
    {
        $service = new UrlService(null, null, new RequestStack());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mercure fallback URL resolution requires an active HTTP request.');
        $service->getClientSideUrl();
    }

    public function testDefaultClientUrlIncludesNonStandardPort(): void
    {
        $service = new UrlService(
            null,
            null,
            $this->createRequestStackWithRequest('http://localhost:8080')
        );

        $this->assertSame('http://localhost:8080/hub', $service->getClientSideUrl());
    }

    private function createRequestStackWithRequest(string $uri): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create(uri: $uri));

        return $requestStack;
    }
}
