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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\ExecutionEngine\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\ExecutionEngine\Messages\DeleteConfigurationsMessage;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\ProviderLoaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

/**
 * @internal
 */
#[AsMessageHandler]
final class DeleteConfigurationsHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly ProviderLoaderInterface $providerLoader,
        private readonly PublishServiceInterface $publishService,
        private readonly UserTopicServiceInterface $userTopicService,
        private readonly UserResolverInterface $userResolver,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(DeleteConfigurationsMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $user = $this->userResolver->getById($jobRun->getOwnerId());
        if ($user === null || !$user->isAdmin()) {
            return;
        }

        $type = (string) $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIGURATION_TYPE->value);
        $ids = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIGURATION_IDS->value);
        $totalItems = count($ids);
        $stepName = $this->getJobStep($message)->getName();
        $provider = $this->providerLoader->resolve($type);

        foreach ($ids as $id) {
            try {
                $provider->delete([(string) $id]);
            } catch (Exception $e) {
                $this->abort($this->getAbortData(
                    Config::OWNERSHIP_MANAGEMENT_DELETE_FAILED->value,
                    ['id' => $id, 'message' => $e->getMessage()]
                ));
            }

            $this->updateProgress(
                $this->publishService,
                $this->userTopicService,
                $jobRun,
                $stepName,
                $totalItems,
            );
        }
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired([
            StepConfig::CONFIGURATION_TYPE->value,
            StepConfig::CONFIGURATION_IDS->value,
        ]);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIGURATION_TYPE->value,
            StepConfig::CONFIG_TYPE_STRING->value
        );
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIGURATION_IDS->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
    }
}
