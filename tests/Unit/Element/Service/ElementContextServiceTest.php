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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementContextService;
use RuntimeException;

/**
 * @internal
 */
final class ElementContextServiceTest extends Unit
{
    private const array CLI_CONTEXT = [
        'hideUnpublishedObjects' => true,
        'hideUnpublishedDocuments' => true,
        'fallbackValues' => true,
    ];

    private const array STUDIO_CONTEXT = [
        'hideUnpublishedObjects' => false,
        'hideUnpublishedDocuments' => false,
        'fallbackValues' => false,
    ];

    /** @var array<string, bool> */
    private array $context = self::CLI_CONTEXT;

    public function testRunInStudioContextAppliesStudioContextDuringCallbackAndRestoresCliContext(): void
    {
        $contextInCallback = null;

        $this->createService()->runInStudioContext(function () use (&$contextInCallback): void {
            $contextInCallback = $this->context;
        });

        $this->assertSame(self::STUDIO_CONTEXT, $contextInCallback);
        $this->assertSame(self::CLI_CONTEXT, $this->context);
    }

    public function testRunInStudioContextRestoresContextWhenCallbackThrows(): void
    {
        $service = $this->createService();

        try {
            $service->runInStudioContext(static function (): void {
                throw new RuntimeException('patch failed');
            });
            $this->fail('The exception of the callback must not be swallowed');
        } catch (RuntimeException $exception) {
            $this->assertSame('patch failed', $exception->getMessage());
        }

        $this->assertSame(self::CLI_CONTEXT, $this->context);
    }

    public function testRunInStudioContextRestoresPreviousStudioContext(): void
    {
        $this->context = self::STUDIO_CONTEXT;

        $this->createService()->runInStudioContext(static function (): void {
        });

        $this->assertSame(self::STUDIO_CONTEXT, $this->context);
    }

    private function createService(): ElementContextService
    {
        $dataObjectResolver = $this->createMock(DataObjectResolverInterface::class);
        $dataObjectResolver->method('doHideUnpublished')
            ->willReturnCallback(fn () => $this->context['hideUnpublishedObjects']);
        $dataObjectResolver->method('setHideUnpublished')->willReturnCallback(function (bool $hide): void {
            $this->context['hideUnpublishedObjects'] = $hide;
        });

        $documentResolver = $this->createMock(DocumentResolverInterface::class);
        $documentResolver->method('doHideUnpublished')
            ->willReturnCallback(fn () => $this->context['hideUnpublishedDocuments']);
        $documentResolver->method('setHideUnpublished')->willReturnCallback(function (bool $hide): void {
            $this->context['hideUnpublishedDocuments'] = $hide;
        });

        $localizedFieldResolver = $this->createMock(LocalizedFieldResolverInterface::class);
        $localizedFieldResolver->method('getGetFallbackValues')
            ->willReturnCallback(fn () => $this->context['fallbackValues']);
        $localizedFieldResolver->method('setGetFallbackValues')->willReturnCallback(function (bool $fallback): void {
            $this->context['fallbackValues'] = $fallback;
        });

        return new ElementContextService(
            $dataObjectResolver,
            $documentResolver,
            $localizedFieldResolver,
        );
    }
}
