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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Handler;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Messages\DeleteItemsMessage;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RecycleBinServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class DeleteItemsHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly RecycleBinServiceInterface $recycleBinService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserTopicServiceInterface $userTopicService,

    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(DeleteItemsMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun) || !$message->getElement()) {
            return;
        }

        $id = $message->getElement()->getId();

        try {
            $this->recycleBinService->deleteItem($id);
        } catch (Exception $e) {
            $this->abort($this->getAbortData(
                Config::RECYCLE_BIN_DELETE_FAILED->value,
                ['id' => $id, 'message' => $e->getMessage()]
            ));
        }

        $this->updateProgress($this->publishService, $this->userTopicService, $jobRun, $this->getJobStep($message)->getName());
    }
}
