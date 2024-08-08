<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\RecycleBinMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Model\Element\ElementDescriptor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class RecycleBinHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly ElementDeleteServiceInterface $elementDeleteService,
        private readonly ElementServiceInterface $elementService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(RecycleBinMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $validatedParameters = $this->validateFullParameters(
            $message,
            $jobRun,
            $this->userResolver
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $subject = $validatedParameters->getSubject();
        $element = $this->getElementById(
            new ElementDescriptor(
                $subject->getType(),
                $subject->getId()
            ),
            $user,
            $this->elementService
        );
        $this->elementDeleteService->addElementToRecycleBin($element, $user);

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
