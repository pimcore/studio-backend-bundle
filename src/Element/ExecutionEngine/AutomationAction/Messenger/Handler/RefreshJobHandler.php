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

namespace Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\RefreshJobMessage;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

/**
 * @internal
 */
#[AsMessageHandler]
final class RefreshJobHandler extends AbstractHandler
{
    use ElementProviderTrait;
    use HandlerProgressTrait;

    public function __construct(
        private readonly PublishServiceInterface $publishService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(RefreshJobMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $selectedElementCount = count($jobRun->getJob()?->getSelectedElements() ?? []);
        if ($selectedElementCount === $jobRun->getTotalElements()) {
            return;
        }

        $jobRun->setTotalElements($selectedElementCount);

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
