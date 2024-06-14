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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\StreamedResponseTrait;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepositoryInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
final readonly class VersionBinaryService implements VersionBinaryServiceInterface
{
    use ElementProviderTrait;
    use StreamedResponseTrait;

    public function __construct(
        private VersionRepositoryInterface $repository,
        private VersionDetailServiceInterface $versionDetailService
    ) {
    }

    /**
     * @throws AccessDeniedException|NotFoundException|InvalidElementTypeException
     */
    public function downloadAsset(
        int $id,
        UserInterface $user
    ): StreamedResponse {
        $version = $this->repository->getVersionById($id);
        $element = $this->repository->getElementFromVersion($version, $user);
        if (!$element instanceof Asset) {
            throw new InvalidElementTypeException($element->getType());
        }

        return $this->getStreamedResponse(
            $element,
            HttpResponseHeaders::ATTACHMENT_TYPE->value,
            [],
            $this->versionDetailService->getAssetFileSize($element) ?? $element->getFileSize()
        );
    }

    /**
     * @throws AccessDeniedException|NotFoundException|InvalidElementTypeException
     */
    public function streamImage(
        int $id,
        UserInterface $user
    ): StreamedResponse {
        $version = $this->repository->getVersionById($id);
        $element = $this->repository->getElementFromVersion($version, $user);
        if (!$element instanceof Asset\Image) {
            throw new InvalidElementTypeException($element->getType());
        }

        return $this->getStreamedResponse(
            $element,
            HttpResponseHeaders::INLINE_TYPE->value,
            [],
            $this->versionDetailService->getAssetFileSize($element) ?? $element->getFileSize()
        );
    }
}
