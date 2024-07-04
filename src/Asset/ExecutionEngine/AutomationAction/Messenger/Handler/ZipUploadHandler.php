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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipUploadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\CloneEnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\HandlerProgressTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class ZipUploadHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly ElementServiceInterface $elementService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly UploadServiceInterface $uploadService
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ZipUploadMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        $validatedParameters = $this->validateJobParameters(
            $message,
            $jobRun,
            $this->userResolver,
            [
                CloneEnvironmentVariables::PARENT_ID->value,
            ],
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $environmentVariables = $validatedParameters->getEnvironmentData();

        try {
            $archivePath = $validatedParameters->getSubject()->getType();


            $this->uploadService->uploadAsset(
                $environmentVariables[CloneEnvironmentVariables::PARENT_ID->value],
                $file,
                $user
            );
        } catch (Exception $exception) {
            $this->abort($this->getAbortData(
                Config::ZIP_FILE_UPLOAD_FAILED_MESSAGE->value,
                ['message' => $exception->getMessage()],
            ));
        }

       $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
