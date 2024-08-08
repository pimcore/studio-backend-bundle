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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service\ExecutionEngine;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\CloneParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\UserInterface;

interface CloneServiceInterface
{
    public const OBJECT_TO_CLONE = 'objectToClone';

    /**
     * @throws AccessDeniedException
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function cloneDataObjects(
        int $sourceId,
        int $parentId,
        CloneParameters $parameters,
    ): ?int;

    public function cloneDataObject(
        DataObject $source,
        DataObject $parent,
        UserInterface $user
    ): AbstractObject;

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getNewCloneTarget(
        UserInterface $user,
        DataObject $source,
        int $originalParentId,
        int $parentId,
    ): DataObject;
}
