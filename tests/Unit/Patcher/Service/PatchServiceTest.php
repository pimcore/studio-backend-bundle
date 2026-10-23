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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Patcher\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\CoauthorService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementIndexServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\AdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
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
final class PatchServiceTest extends Unit
{
    private Asset $element;

    private UserInterface $user;

    private CoauthorContext $coauthorContext;

    protected function _before(): void
    {
        $this->element = $this->makeEmpty(Asset::class);
        $this->user = $this->makeEmpty(UserInterface::class);
        $this->coauthorContext = new CoauthorContext();
    }

    public function testPatchActivatesCoauthorContextDuringSaveAndRestoresAfter(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = [
                'active' => $this->coauthorContext->isActive(),
                'type' => $this->coauthorContext->getType(),
                'coauthor' => $this->coauthorContext->getCoauthor(),
            ];
        });

        $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
            'coauthorType' => 'agent',
            'coauthor' => 'product-data-agent',
        ], $this->user);

        $this->assertSame(
            ['active' => true, 'type' => 'agent', 'coauthor' => 'product-data-agent'],
            $captured[0]
        );
        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertNull($this->coauthorContext->getType());
        $this->assertNull($this->coauthorContext->getCoauthor());
    }

    public function testPatchWithoutCoauthorKeysNeverActivatesContext(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = $this->coauthorContext->isActive();
        });

        $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [], $this->user);

        $this->assertSame([false], $captured);
        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testPatchWithPayloadCoauthorOverridesOuterContextAndRestoresItAfter(): void
    {
        $this->coauthorContext->set('agent', 'data-management');

        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = [
                'type' => $this->coauthorContext->getType(),
                'coauthor' => $this->coauthorContext->getCoauthor(),
            ];
        });

        $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
            'coauthorType' => 'automation',
            'coauthor' => 'import-job',
        ], $this->user);

        $this->assertSame(['type' => 'automation', 'coauthor' => 'import-job'], $captured[0]);
        $this->assertSame('agent', $this->coauthorContext->getType());
        $this->assertSame('data-management', $this->coauthorContext->getCoauthor());
    }

    public function testPatchRestoresContextWhenSaveThrows(): void
    {
        $service = $this->createService(static function (): void {
            throw new RuntimeException('boom');
        });

        try {
            $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
                'coauthorType' => 'agent',
                'coauthor' => 'product-data-agent',
            ], $this->user);
            $this->fail('Expected ElementSavingFailedException was not thrown');
        } catch (ElementSavingFailedException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
        }

        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertNull($this->coauthorContext->getType());
        $this->assertNull($this->coauthorContext->getCoauthor());
    }

    public function testPatchRestoresOuterContextWhenSaveThrows(): void
    {
        $this->coauthorContext->set('agent', 'data-management');

        $service = $this->createService(static function (): void {
            throw new RuntimeException('boom');
        });

        try {
            $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
                'coauthorType' => 'automation',
                'coauthor' => 'import-job',
            ], $this->user);
            $this->fail('Expected ElementSavingFailedException was not thrown');
        } catch (ElementSavingFailedException) {
            // expected - assertions happen after the try/catch
        }

        $this->assertSame('agent', $this->coauthorContext->getType());
        $this->assertSame('data-management', $this->coauthorContext->getCoauthor());
    }

    public function testPatchWithEmptyStringCoauthorKeysNeverActivatesContext(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = $this->coauthorContext->isActive();
        });

        $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
            'coauthorType' => '',
            'coauthor' => '',
        ], $this->user);

        $this->assertSame([false], $captured);
        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testPatchRejectsOverlongCoauthorTypeBeforeSaving(): void
    {
        $saved = false;
        $service = $this->createService(static function () use (&$saved): void {
            $saved = true;
        });

        $this->expectException(InvalidArgumentException::class);

        try {
            $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
                'coauthorType' => str_repeat('a', VersionCoauthor::MAX_TYPE_LENGTH + 1),
                'coauthor' => 'product-data-agent',
            ], $this->user);
        } finally {
            $this->assertFalse($saved);
            $this->assertFalse($this->coauthorContext->isActive());
        }
    }

    public function testPatchRejectsOverlongCoauthorBeforeSaving(): void
    {
        $saved = false;
        $service = $this->createService(static function () use (&$saved): void {
            $saved = true;
        });

        $this->expectException(InvalidArgumentException::class);

        try {
            $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
                'coauthorType' => 'agent',
                'coauthor' => str_repeat('a', VersionCoauthor::MAX_COAUTHOR_LENGTH + 1),
            ], $this->user);
        } finally {
            $this->assertFalse($saved);
            $this->assertFalse($this->coauthorContext->isActive());
        }
    }

    public function testPatchAcceptsCoauthorValuesAtTheLengthLimit(): void
    {
        $coauthorType = str_repeat('a', VersionCoauthor::MAX_TYPE_LENGTH);
        $coauthor = str_repeat('b', VersionCoauthor::MAX_COAUTHOR_LENGTH);

        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = [
                'type' => $this->coauthorContext->getType(),
                'coauthor' => $this->coauthorContext->getCoauthor(),
            ];
        });

        $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
            'coauthorType' => $coauthorType,
            'coauthor' => $coauthor,
        ], $this->user);

        $this->assertSame(['type' => $coauthorType, 'coauthor' => $coauthor], $captured[0]);
    }

    private function createService(callable $onSave): PatchService
    {
        return new PatchService(
            adapterLoader: $this->makeEmpty(AdapterLoaderInterface::class, [
                'loadAdapters' => [],
            ]),
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class),
            elementService: $this->makeEmpty(ElementServiceInterface::class),
            jobExecutionAgent: $this->makeEmpty(JobExecutionAgentInterface::class),
            indexService: $this->makeEmpty(ElementIndexServiceInterface::class),
            elementSaveService: $this->makeEmpty(ElementSaveServiceInterface::class, [
                'save' => $onSave,
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class),
            coauthorService: new CoauthorService($this->coauthorContext),
        );
    }
}
