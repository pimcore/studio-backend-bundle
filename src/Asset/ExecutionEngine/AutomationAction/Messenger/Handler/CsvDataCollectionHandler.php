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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constant\Csv;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class CsvDataCollectionHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    private const ARRAY_TYPE = 'array';

    public function __construct(
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly GridServiceInterface $gridService,
        private readonly AssetServiceInterface $assetService
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(CsvCollectionMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $user = $this->userResolver->getById($jobRun->getOwnerId());

        if ($user === null) {
            $this->abort($this->getAbortData(
                Config::USER_NOT_FOUND_MESSAGE->value,
                [
                    'userId' => $jobRun->getOwnerId(),
                ]
            ));
        }

        $jobAsset = $this->extractConfigFieldFromJobStepConfig($message, Csv::ASSET_TO_EXPORT->value);

        $asset = $this->assetService->getAssetForUser($jobAsset['id'], $user);

        if ($asset->getType() === ElementTypes::TYPE_FOLDER) {
            $this->abort($this->getAbortData(
                Config::ELEMENT_FOLDER_COLLECTION_NOT_SUPPORTED->value,
                [
                    'folderId' => $asset->getId(),
                ]
            ));

            return;
        }

        $columnCollection = $this->gridService->getConfigurationFromArray(
            $this->extractConfigFieldFromJobStepConfig($message, Csv::JOB_STEP_CONFIG_CONFIGURATION->value),
            true
        );

        try {
            $assetData = [
                $asset->getId() => $this->gridService->getGridValuesForElement(
                    $columnCollection,
                    $asset,
                    ElementTypes::TYPE_ASSET
                ),
            ];

            $this->updateContextArrayValues($jobRun, Csv::ASSET_EXPORT_DATA->value, $assetData);
        } catch (Exception $e) {
            $this->abort($this->getAbortData(
                Config::CSV_DATA_COLLECTION_FAILED_MESSAGE->value,
                [
                    'id' => $asset->getId(),
                    'message' => $e->getMessage(),
                ]
            ));
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(Csv::ASSET_TO_EXPORT->value);
        $this->stepConfiguration->setAllowedTypes(
            Csv::ASSET_TO_EXPORT->value,
            self::ARRAY_TYPE
        );
        $this->stepConfiguration->setRequired(Csv::JOB_STEP_CONFIG_CONFIGURATION->value);
        $this->stepConfiguration->setAllowedTypes(
            Csv::JOB_STEP_CONFIG_CONFIGURATION->value,
            self::ARRAY_TYPE
        );
    }
}
