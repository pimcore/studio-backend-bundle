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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\HandlerProgressTrait;
use Pimcore\Model\Asset;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function array_key_exists;
use function in_array;

/**
 * @internal
 */
#[AsMessageHandler]
final class ZipCreationHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly PublishServiceInterface $publishService,
        private readonly ElementServiceInterface $elementService,
        private readonly UserResolverInterface $userResolver,
        private readonly ZipServiceInterface $zipService
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ZipCreationMessage $message): void
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

        $context = $jobRun->getContext();

        if (!array_key_exists(ZipServiceInterface::ASSETS_INDEX, $context)) {
            $this->abortAction(
                'no_assets_found',
                [],
                Config::CONTEXT->value,
                NotFoundException::class
            );
        }

        $jobAsset = $validatedParameters->getSubject();

        if (!in_array($jobAsset->getId(), $context[ZipServiceInterface::ASSETS_INDEX], true)) {
            $this->abortAction(
                'asset_permission_denied',
                [],
                Config::CONTEXT->value,
                AccessDeniedException::class
            );
        }

        $archive = $this->zipService->getZipArchive($jobRun->getId());
        if (!$archive) {
            $this->abortAction(
                'zip_archive_not_found',
                [],
                Config::CONTEXT->value,
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

        $this->zipService->addFile($archive, $asset);

        $archive->close();

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
