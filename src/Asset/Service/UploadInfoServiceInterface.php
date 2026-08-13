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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetBatchInfo;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface UploadInfoServiceInterface
{
    /**
     * Resolves which of the given file names are already taken in the target folder.
     * The result contains one entry per requested name, in the order they were given.
     *
     * @param array<string> $fileNames
     *
     * @return array<AssetBatchInfo>
     *
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function filesExist(
        int $parentId,
        array $fileNames,
        UserInterface $user
    ): array;
}
