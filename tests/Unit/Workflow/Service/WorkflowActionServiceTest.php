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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\ActionSubmitter\TransitionActionSubmitterInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Response\ActionSubmissionResponse;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowActionService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Transition;
use ReflectionClass;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;
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
            $this->makeEmpty(UserServiceInterface::class),
            $this->makeEmpty(DataServiceInterface::class)
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
     * Workflow actions must be applied to the user's latest draft, like the classic admin UI does and
     * like the allowed transitions are computed, so that the draft's data is what gets saved/published
     * (see pimcore/platform-version#455).
     */
    public function testSubmitActionAppliesActionToLatestDraft(): void
    {
        $draft = $this->makeEmpty(Concrete::class);
        // Version is final and its constructor needs the container, so build it without the constructor
        $version = (new ReflectionClass(Version::class))->newInstanceWithoutConstructor();
        $version->setData($draft);
        $element = $this->createElementWithLatestVersion($version);

        $submittedElement = null;
        $service = $this->createServiceForSubmit(
            $element,
            $submittedElement,
            $this->makeEmpty(DataServiceInterface::class, [
                'handleDraftData' => Expected::once(function (Concrete $draftElement, Concrete $dbElement) use ($draft, $element): void {
                    $this->assertSame($draft, $draftElement);
                    $this->assertSame($element, $dbElement);
                }),
            ])
        );

        $service->submitAction($this->createUser(), $this->createSubmitAction());

        $this->assertSame($draft, $submittedElement, 'The draft loaded from the latest version must be the workflow subject.');
    }

    public function testSubmitActionUsesElementWithoutDraftAsIs(): void
    {
        $element = $this->createElementWithLatestVersion(null);

        $submittedElement = null;
        $service = $this->createServiceForSubmit(
            $element,
            $submittedElement,
            $this->makeEmpty(DataServiceInterface::class, ['handleDraftData' => Expected::never()])
        );

        $service->submitAction($this->createUser(), $this->createSubmitAction());

        $this->assertSame($element, $submittedElement);
    }

    private function createServiceForSubmit(
        ElementInterface $element,
        ?ElementInterface &$submittedElement,
        DataServiceInterface $objectDataService
    ): WorkflowActionService {
        $submitter = $this->makeEmpty(TransitionActionSubmitterInterface::class, [
            'submit' => function (ElementInterface $subject) use (&$submittedElement): ActionSubmissionResponse {
                $submittedElement = $subject;

                return new ActionSubmissionResponse('wf', 'go', 'transition');
            },
        ]);

        return new WorkflowActionService(
            $this->makeEmpty(Manager::class),
            $this->makeEmpty(Registry::class, ['get' => $this->makeEmpty(WorkflowInterface::class)]),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(ServiceProviderInterface::class, ['has' => true, 'get' => $submitter]),
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => $element]),
            $this->makeEmpty(UserServiceInterface::class),
            $objectDataService
        );
    }

    /**
     * getLatestVersion() is a magic method of data objects (forwarded to the DAO via __call),
     * so the stub has to answer it through __call.
     */
    private function createElementWithLatestVersion(?Version $version): Concrete
    {
        return $this->makeEmpty(Concrete::class, [
            '__call' => static fn (string $method, array $arguments): ?Version => $method === 'getLatestVersion' ? $version : null,
        ]);
    }

    private function createUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => 7]);
    }

    private function createSubmitAction(): SubmitAction
    {
        return new SubmitAction('transition', 15, ElementTypes::TYPE_DATA_OBJECT, 'wf', 'go');
    }
}
