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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
interface ImageServiceInterface
{
    /**
     * @throws ForbiddenException|NotFoundException|UserNotFoundException
     */
    public function uploadUserImage(UploadedFile $file, int $userId): void;

    /**
     * @throws ForbiddenException|NotFoundException|UserNotFoundException
     */
    public function getImageFromUserAsStreamedResponse(int $userId): StreamedResponse;

    /**
     * @throws ForbiddenException|NotFoundException|UserNotFoundException
     */
    public function deleteUserImage(int $userId): void;
}
