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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Setting\Provider;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ConfigResolver;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SystemSettingsProvider;
use Pimcore\Model\Document;
use ReflectionClass;

final class SystemSettingsProviderTest extends Unit
{
    public function testGetDocumentSettingsWhenErrorPagesHaveNeverBeenSaved(): void
    {
        $provider = $this->createProvider(
            documents: [
                'versions' => ['days' => 10, 'steps' => 5],
            ],
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class),
            elementDataService: $this->makeEmpty(ElementDataServiceInterface::class)
        );

        $documents = $this->invokeGetDocumentSettings($provider);

        $this->assertSame(
            [
                'versions' => ['days' => 10, 'steps' => 5],
                'error_pages' => [],
            ],
            $documents
        );
    }

    public function testGetDocumentSettingsResolvesExistingErrorPages(): void
    {
        $resolvedElementData = new RelatedElementData(1, 'document', 'page', '/en/error', true);
        $documentMock = $this->makeEmpty(Document::class);

        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementByPath' => function (string $type, string $path) use ($documentMock) {
                return $path === '/en/error' ? $documentMock : null;
            },
        ]);

        $elementDataService = $this->makeEmpty(ElementDataServiceInterface::class, [
            'getRelatedElementData' => $resolvedElementData,
        ]);

        $provider = $this->createProvider(
            documents: [
                'error_pages' => [
                    'default' => '/en/error',
                    'localized' => [
                        'en' => '/en/error',
                        'de' => '',
                    ],
                ],
            ],
            serviceResolver: $serviceResolver,
            elementDataService: $elementDataService
        );

        $documents = $this->invokeGetDocumentSettings($provider);

        $this->assertSame($resolvedElementData, $documents['error_pages']['default']);
        $this->assertSame($resolvedElementData, $documents['error_pages']['localized']['en']);
        $this->assertSame('', $documents['error_pages']['localized']['de']);
    }

    private function createProvider(
        array $documents,
        ServiceResolverInterface $serviceResolver,
        ElementDataServiceInterface $elementDataService
    ): SystemSettingsProvider {
        $reflection = new ReflectionClass(SystemSettingsProvider::class);
        $provider = $reflection->newInstanceWithoutConstructor();

        $this->setPrivateProperty($reflection, $provider, 'systemSettings', [
            'documents' => $documents,
        ]);
        $this->setPrivateProperty($reflection, $provider, 'configResolver', new ConfigResolver());
        $this->setPrivateProperty($reflection, $provider, 'serviceResolver', $serviceResolver);
        $this->setPrivateProperty($reflection, $provider, 'elementDataService', $elementDataService);

        return $provider;
    }

    private function setPrivateProperty(
        ReflectionClass $reflection,
        SystemSettingsProvider $provider,
        string $name,
        mixed $value
    ): void {
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($provider, $value);
    }

    private function invokeGetDocumentSettings(SystemSettingsProvider $provider): array
    {
        $method = (new ReflectionClass($provider))->getMethod('getDocumentSettings');
        $method->setAccessible(true);

        return $method->invoke($provider);
    }
}
