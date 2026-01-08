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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentDetail;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterServiceTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\Document as DocumentModel;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface DocumentServiceInterface
{
    /**
     * @throws ElementExistsException|ElementSavingFailedException|ForbiddenException
     * @throws InvalidArgumentException|NotFoundException|UserNotFoundException
     */
    public function addDocument(int $parentId, DocumentAddParameters $parameters): int;

    /**
     * @throws InvalidFilterServiceTypeException|SearchException|InvalidQueryTypeException|InvalidFilterTypeException
     */
    public function getDocuments(ElementParameters $parameters): Collection;

    /**
     * @throws SearchException|NotFoundException|UserNotFoundException
     */
    public function getDocument(int $id, bool $getDetailData = true): DocumentDetail;

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDocumentForUser(int $id, UserInterface $user): DocumentDetail;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getDocumentElement(UserInterface $user, int $documentId): DocumentModel;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getDocumentElementByPath(UserInterface $user, string $path): DocumentModel;

    /**
     * @return DocumentType[]
     */
    public function getDocumentTypes(): array;
}
