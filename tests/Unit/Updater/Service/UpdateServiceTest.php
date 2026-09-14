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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Updater\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface as DataObjectDataService;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DataServiceInterface as DocumentDataService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\CoauthorService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementIndexServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\VersionDraftElementResolver;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\AdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\UpdateService;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\VersionCoauthor;
use Pimcore\Model\Asset;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version\CoauthorContext;
use RuntimeException;
use function str_repeat;

/**
 * @internal
 */
final class UpdateServiceTest extends Unit
{
    private const int ELEMENT_ID = 42;

    private Asset $element;

    private CoauthorContext $coauthorContext;

    protected function _before(): void
    {
        $this->element = $this->makeEmpty(Asset::class);
        $this->coauthorContext = new CoauthorContext();
    }

    public function testUpdateActivatesCoauthorContextDuringSaveAndRestoresAfter(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = [
                'active' => $this->coauthorContext->isActive(),
                'type' => $this->coauthorContext->getType(),
                'coauthor' => $this->coauthorContext->getCoauthor(),
            ];
        });

        $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
            'coauthorType' => 'agent',
            'coauthor' => 'product-data-agent',
        ]);

        $this->assertSame(
            ['active' => true, 'type' => 'agent', 'coauthor' => 'product-data-agent'],
            $captured[0]
        );
        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertNull($this->coauthorContext->getType());
        $this->assertNull($this->coauthorContext->getCoauthor());
    }

    public function testUpdateWithoutCoauthorKeysNeverActivatesContext(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = $this->coauthorContext->isActive();
        });

        $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, []);

        $this->assertSame([false], $captured);
        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testUpdateWithPayloadCoauthorOverridesOuterContextAndRestoresItAfter(): void
    {
        $this->coauthorContext->set('agent', 'data-management');

        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = [
                'type' => $this->coauthorContext->getType(),
                'coauthor' => $this->coauthorContext->getCoauthor(),
            ];
        });

        $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
            'coauthorType' => 'automation',
            'coauthor' => 'import-job',
        ]);

        $this->assertSame(['type' => 'automation', 'coauthor' => 'import-job'], $captured[0]);
        $this->assertSame('agent', $this->coauthorContext->getType());
        $this->assertSame('data-management', $this->coauthorContext->getCoauthor());
    }

    public function testUpdateRestoresContextWhenSaveThrows(): void
    {
        $service = $this->createService(static function (): void {
            throw new RuntimeException('boom');
        });

        try {
            $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
                'coauthorType' => 'agent',
                'coauthor' => 'product-data-agent',
            ]);
            $this->fail('Expected ElementSavingFailedException was not thrown');
        } catch (ElementSavingFailedException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
        }

        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertNull($this->coauthorContext->getType());
        $this->assertNull($this->coauthorContext->getCoauthor());
    }

    public function testUpdateRestoresOuterContextWhenSaveThrows(): void
    {
        $this->coauthorContext->set('agent', 'data-management');

        $service = $this->createService(static function (): void {
            throw new RuntimeException('boom');
        });

        try {
            $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
                'coauthorType' => 'automation',
                'coauthor' => 'import-job',
            ]);
            $this->fail('Expected ElementSavingFailedException was not thrown');
        } catch (ElementSavingFailedException) {
            // expected - assertions happen after the try/catch
        }

        $this->assertSame('agent', $this->coauthorContext->getType());
        $this->assertSame('data-management', $this->coauthorContext->getCoauthor());
    }

    public function testUpdateWithEmptyStringCoauthorKeysNeverActivatesContext(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = $this->coauthorContext->isActive();
        });

        $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
            'coauthorType' => '',
            'coauthor' => '',
        ]);

        $this->assertSame([false], $captured);
        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testUpdateWithOnlyCoauthorTypeNeverActivatesContext(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = $this->coauthorContext->isActive();
        });

        $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
            'coauthorType' => 'agent',
        ]);

        $this->assertSame([false], $captured);
        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testUpdateRejectsOverlongCoauthorTypeBeforeSaving(): void
    {
        $saved = false;
        $service = $this->createService(static function () use (&$saved): void {
            $saved = true;
        });

        $this->expectException(InvalidArgumentException::class);

        try {
            $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
                'coauthorType' => str_repeat('a', VersionCoauthor::MAX_TYPE_LENGTH + 1),
                'coauthor' => 'product-data-agent',
            ]);
        } finally {
            $this->assertFalse($saved);
            $this->assertFalse($this->coauthorContext->isActive());
        }
    }

    public function testUpdateRejectsOverlongCoauthorBeforeSaving(): void
    {
        $saved = false;
        $service = $this->createService(static function () use (&$saved): void {
            $saved = true;
        });

        $this->expectException(InvalidArgumentException::class);

        try {
            $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
                'coauthorType' => 'agent',
                'coauthor' => str_repeat('b', VersionCoauthor::MAX_COAUTHOR_LENGTH + 1),
            ]);
        } finally {
            $this->assertFalse($saved);
            $this->assertFalse($this->coauthorContext->isActive());
        }
    }

    private function createService(callable $onSave): UpdateService
    {
        return new UpdateService(
            adapterLoader: $this->makeEmpty(AdapterLoaderInterface::class, [
                'loadAdapters' => [],
            ]),
            objectDataService: $this->makeEmpty(DataObjectDataService::class),
            documentDataService: $this->makeEmpty(DocumentDataService::class),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class),
            ]),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => $this->element,
            ]),
            indexService: $this->makeEmpty(ElementIndexServiceInterface::class),
            elementSaveService: $this->makeEmpty(ElementSaveServiceInterface::class, [
                'save' => $onSave,
            ]),
            coauthorService: new CoauthorService($this->coauthorContext),
            draftElementResolver: new VersionDraftElementResolver(),
        );
    }
}
