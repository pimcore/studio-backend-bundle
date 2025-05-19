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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service\ExecutionEngine;

use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\CloneData;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentCloneParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Model\Document;
use Pimcore\Model\UserInterface;

interface CloneServiceInterface
{
    public const string DOCUMENT_TO_CLONE = 'documentToClone';

    /**
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function cloneDocuments(
        int $sourceId,
        int $parentId,
        DocumentCloneParameters $parameters,
    ): ?int;

    /**
     * @throws ForbiddenException|ValidationFailedException
     */
    public function cloneDocument(
        Document $source,
        Document $parent,
        UserInterface $user,
        CloneData $cloneData
    ): Document;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getNewCloneTarget(
        UserInterface $user,
        Document $source,
        int $originalParentId,
        int $parentId,
    ): Document;
}
