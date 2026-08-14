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
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;
use Pimcore\Model\Document\Page;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\Manager;

/**
 * @internal
 */
final class ElementSaveServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testPublishSucceedsWhenWorkflowDoesNotDenyIt(): void
    {
        $publishedValue = null;
        $element = null;
        $element = $this->makeEmpty(Page::class, [
            'setPublished' => function (bool $published) use (&$publishedValue, &$element) {
                $publishedValue = $published;

                return $element;
            },
        ]);
        $service = $this->createElementSaveService(isDeniedInWorkflow: false);

        $service->save($element, $this->makeUser(), ElementSaveTasks::PUBLISH->value);

        $this->assertTrue($publishedValue);
    }

    /**
     * @throws Exception
     */
    public function testPublishThrowsForbiddenWhenWorkflowDeniesIt(): void
    {
        $element = $this->makeEmpty(Page::class, [
            'setPublished' => function () {
                $this->fail('setPublished() must not be called when the workflow denies publishing');
            },
        ]);
        $service = $this->createElementSaveService(isDeniedInWorkflow: true);

        $this->expectException(ForbiddenException::class);
        $service->save($element, $this->makeUser(), ElementSaveTasks::PUBLISH->value);
    }

    /**
     * @throws Exception
     */
    public function testUnpublishSucceedsWhenWorkflowDoesNotDenyIt(): void
    {
        $publishedValue = null;
        $element = null;
        $element = $this->makeEmpty(Page::class, [
            'setPublished' => function (bool $published) use (&$publishedValue, &$element) {
                $publishedValue = $published;

                return $element;
            },
        ]);
        $service = $this->createElementSaveService(isDeniedInWorkflow: false);

        $service->save($element, $this->makeUser(), ElementSaveTasks::UNPUBLISH->value);

        $this->assertFalse($publishedValue);
    }

    /**
     * @throws Exception
     */
    public function testUnpublishThrowsForbiddenWhenWorkflowDeniesIt(): void
    {
        $element = $this->makeEmpty(Page::class, [
            'setPublished' => function () {
                $this->fail('setPublished() must not be called when the workflow denies unpublishing');
            },
        ]);
        $service = $this->createElementSaveService(isDeniedInWorkflow: true);

        $this->expectException(ForbiddenException::class);
        $service->save($element, $this->makeUser(), ElementSaveTasks::UNPUBLISH->value);
    }

    /**
     * @throws Exception
     */
    private function createElementSaveService(bool $isDeniedInWorkflow): ElementSaveService
    {
        $synchronousProcessingService = $this->makeEmpty(SynchronousProcessingServiceInterface::class);
        $securityService = $this->makeEmpty(SecurityServiceInterface::class);
        $workflowManager = $this->makeEmpty(Manager::class, [
            'isDeniedInWorkflow' => $isDeniedInWorkflow,
        ]);

        return new ElementSaveService($synchronousProcessingService, $securityService, $workflowManager);
    }

    /**
     * @throws Exception
     */
    private function makeUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => 1]);
    }
}
