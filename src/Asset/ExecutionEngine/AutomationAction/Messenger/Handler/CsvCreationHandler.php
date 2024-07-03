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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorService;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\HandlerProgressTrait;
use Pimcore\Model\Asset;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class CsvCreationHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly PublishServiceInterface $publishService,
        private readonly ElementServiceInterface $elementService,
        private readonly UserResolverInterface $userResolver,
        private readonly CsvServiceInterface $csvService,
        private readonly GridServiceInterface $gridService
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(CsvCreationMessage $message): void
    {
        $jobRun = $this->getJobRun($message);

        $validatedParameters = $this->validateJobParameters(
            $message,
            $jobRun,
            $this->userResolver
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abortAction(
                $validatedParameters->getTranslationKey(),
                $validatedParameters->getTranslationParameters(),
                TranslatorService::DOMAIN,
                $validatedParameters->getExceptionClassName()
            );
        }

        $context = $jobRun->getContext();

        if (!array_key_exists(ZipServiceInterface::ASSETS_INDEX, $context)) {
            $this->abortAction(
                'no_assets_found',
                [],
                TranslatorService::DOMAIN,
                NotFoundException::class
            );
        }

        $jobAsset = $validatedParameters->getSubject();

        if (!in_array($jobAsset->getId(), $context[ZipServiceInterface::ASSETS_INDEX], true)) {
            $this->abortAction(
                'asset_permission_denied',
                [],
                TranslatorService::DOMAIN,
                AccessDeniedException::class
            );
        }

        $settings = $this->extractConfigFieldFromJobStepConfig($message, 'settings');
        $configuration = $this->gridService->getConfigurationFromArray(
            $this->extractConfigFieldFromJobStepConfig($message, 'configuration')
        );

        $csv = $this->csvService->getCsvFile($jobRun->getId(), $configuration, $settings);

        if (!$csv) {
            $this->abortAction(
                'csv_file_not_found',
                [],
                TranslatorService::DOMAIN,
                NotFoundException::class
            );
        }

        $asset = $this->getElementById(
            $jobAsset,
            $validatedParameters->getUser(),
            $this->elementService
        );
        if (!$asset instanceof Asset) {
            return;
        }
        $assetData = $this->gridService->getGridValuesForElement(
            $configuration,
            $asset,
            ElementTypes::TYPE_ASSET
        );

        $this->csvService->addData($csv, $settings['delimiter'], $assetData);

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired('settings');
        $this->stepConfiguration->setAllowedTypes('settings', 'array');
        $this->stepConfiguration->setRequired('configuration');
        $this->stepConfiguration->setAllowedTypes('configuration', 'array');
    }
}
