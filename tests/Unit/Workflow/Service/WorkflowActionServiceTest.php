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
use Exception;
use InvalidArgumentException;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\WorkflowActionSubmissionException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\WorkflowActionTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowActionService;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Transition;
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
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(UserServiceInterface::class)
        );
    }

    public function testEnrichActionNotes(): void
    {
        $transition = new Transition('toReview', [], []);
        $folder = new Folder();
        $folder->setId(15);
        $this->assertEmpty($this->workflowActionService->enrichActionNotes($transition, $folder, []));
        $enrichedNotes = $this->workflowActionService->enrichActionNotes($transition, $folder, ['notes' => 'This is a note']);
        $this->assertArrayHasKey('commentPrefill', $enrichedNotes);
        $this->assertEmpty($enrichedNotes['commentPrefill']);
        $enrichedNotes = $this->workflowActionService->enrichActionNotes($transition, $folder, ['commentGetterFn' => 'getId']);
        $this->assertEquals(15, $enrichedNotes['commentPrefill']);
    }

    /**
     * @throws Exception
     */
    public function testUnresolvableWorkflowKeepsItsCause(): void
    {
        $cause = new InvalidArgumentException('Unable to find a workflow');
        $service = new WorkflowActionService(
            $this->makeEmpty(Manager::class),
            $this->makeEmpty(Registry::class, [
                'get' => static function () use ($cause): never {
                    throw $cause;
                },
            ]),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(ServiceProviderInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => new Folder()]),
            $this->makeEmpty(UserServiceInterface::class)
        );

        try {
            $service->submitAction(
                $this->makeEmpty(UserInterface::class),
                new SubmitAction(
                    WorkflowActionTypes::TRANSITION_ACTION,
                    1,
                    ElementTypes::TYPE_DATA_OBJECT,
                    'review',
                    'publish'
                )
            );
            $this->fail('Expected WorkflowActionSubmissionException');
        } catch (WorkflowActionSubmissionException $exception) {
            $this->assertSame($cause, $exception->getPrevious());
        }
    }
}
