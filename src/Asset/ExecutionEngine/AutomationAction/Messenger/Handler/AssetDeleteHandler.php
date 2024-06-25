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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetDeleteMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\DeleteService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\User;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class AssetDeleteHandler extends AbstractHandler
{
    public function __construct(
        private readonly ElementDeleteServiceInterface $elementDeleteService,
        private readonly ElementServiceInterface $elementService,
        private readonly UserResolverInterface $userResolver
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(AssetDeleteMessage $message): void
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
                Config::CONTEXT->value,
                $validatedParameters->getExceptionClassName()
            );
        }

        $user = $validatedParameters->getUser();
        $config = $this->getCurrentJobStepConfig($message);
        $assetId = $config[DeleteService::ASSET_TO_DELETE];
        $parentAsset = $validatedParameters->getSubject();
        $assetElement = $this->getElementById(
            new ElementDescriptor(
                ElementTypes::TYPE_ASSET,
                $assetId
            ),
            $user,
            $this->elementService
        );

        if ($assetElement->getId() === $parentAsset->getId()) {
            try {
                $this->elementDeleteService->deleteParentElement($assetElement, $user);
            } catch (Exception $exception) {
                $this->abort($this->getAbortData(
                    Config::ELEMENT_DELETE_FAILED_MESSAGE->value,
                    [
                        'type' => ElementTypes::TYPE_ASSET,
                        'id' => $assetId,
                        'message' => $exception->getMessage(),
                    ],
                ));
            }

            return;
        }

        /** @var User $user because of the core method */
        if (!$assetElement->isAllowed(ElementPermissions::DELETE_PERMISSION, $user)) {
            $messageParameters = $this->getAbortData(
                Config::ELEMENT_PERMISSION_MISSING_MESSAGE->value,
                [
                    'userId' => $user->getId(),
                    'permission' => ElementPermissions::DELETE_PERMISSION,
                    'type' => ElementTypes::TYPE_ASSET,
                    'id' => $assetId,
                ]
            );

            $this->logMessageToJobRun(
                $jobRun,
                $messageParameters->getTranslationKey(),
                $messageParameters->getTranslationParameters()
            );

            return;
        }

        if ($assetElement->isLocked()) {
            $messageParameters = $this->getAbortData(
                Config::ELEMENT_LOCKED_MESSAGE->value,
                [
                    'type' => ElementTypes::TYPE_ASSET,
                    'id' => $assetId,
                ]
            );

            $this->logMessageToJobRun(
                $jobRun,
                $messageParameters->getTranslationKey(),
                $messageParameters->getTranslationParameters()
            );

            return;
        }

        // TODO Send SSE for percentage update
        try {
            $this->elementDeleteService->deleteElement($assetElement, $user);
        } catch (Exception $exception) {
            $this->abort($this->getAbortData(
                Config::ELEMENT_DELETE_FAILED_MESSAGE->value,
                [
                    'type' => ElementTypes::TYPE_ASSET,
                    'id' => $assetId,
                    'message' => $exception->getMessage(),
                ],
            ));
        }
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(DeleteService::ASSET_TO_DELETE);
        $this->stepConfiguration->setAllowedTypes(DeleteService::ASSET_TO_DELETE, 'int');
    }
}
