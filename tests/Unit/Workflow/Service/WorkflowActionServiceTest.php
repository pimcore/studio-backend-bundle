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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowActionService;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class WorkflowActionServiceTest extends Unit
{
    private WorkflowActionService $workflowActionService;

    public function _before(): void
    {
        $this->workflowActionService = new WorkflowActionService(
            $this->makeEmpty(Manager::class),
            $this->makeEmpty(Registry::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(ServiceProviderInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class)
        );
    }

    public function testEnrichActionNotes(): void
    {
        $folder = new Folder();
        $folder->setId(15);
        $this->assertEmpty($this->workflowActionService->enrichActionNotes($folder, []));
        $enrichedNotes = $this->workflowActionService->enrichActionNotes($folder, ['notes' => 'This is a note']);
        $this->assertArrayHasKey('commentPrefill', $enrichedNotes);
        $this->assertEmpty($enrichedNotes['commentPrefill']);
        $enrichedNotes = $this->workflowActionService->enrichActionNotes($folder, ['commentGetterFn' => 'getId']);
        $this->assertEquals(15, $enrichedNotes['commentPrefill']);
    }
}
