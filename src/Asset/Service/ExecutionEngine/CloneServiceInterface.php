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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Model\Asset;
use Pimcore\Model\UserInterface;

interface CloneServiceInterface
{
    /**
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function cloneAssetRecursively(
        int $sourceId,
        int $parentId
    ): ?int;

    public function cloneElement(
        Asset $source,
        Asset $parent,
        UserInterface $user
    ): Asset;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getNewCloneTarget(
        UserInterface $user,
        Asset $source,
        int $originalParentId,
        int $parentId,
    ): Asset;
}
