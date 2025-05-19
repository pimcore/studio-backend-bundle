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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service\ExecutionEngine;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\CloneParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\UserInterface;

interface CloneServiceInterface
{
    public const string OBJECT_TO_CLONE = 'objectToClone';

    /**
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

    /**
     * @throws ElementSavingFailedException|ForbiddenException
     */
    public function cloneDataObject(
        DataObject $source,
        DataObject $parent,
        UserInterface $user
    ): AbstractObject;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getNewCloneTarget(
        UserInterface $user,
        DataObject $source,
        int $originalParentId,
        int $parentId,
    ): DataObject;
}
