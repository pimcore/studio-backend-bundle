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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\DataObjectParameters;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectAddParameters;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectDetail;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterServiceTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject as DataObjectModel;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface DataObjectServiceInterface
{
    /**
     * @throws DatabaseException
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function addDataObject(
        int $parentId,
        DataObjectAddParameters $parameters,
    ): int;

    /**
     * @throws ForbiddenException|InvalidFilterServiceTypeException|InvalidQueryTypeException
     * @throws InvalidFilterTypeException|NotFoundException|SearchException|UserNotFoundException
     */
    public function getDataObjects(DataObjectParameters $parameters): Collection;

    /**
     * @throws SearchException|NotFoundException|UserNotFoundException
     */
    public function getDataObject(int $id, bool $getDetailData = true): DataObjectDetail|DataObjectFolder;

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDataObjectForUser(int $id, UserInterface $user): DataObjectDetail|DataObjectFolder;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getDataObjectElement(
        UserInterface $user,
        int $dataObjectId,
    ): DataObjectModel;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getDataObjectElementByPath(
        UserInterface $user,
        string $path,
    ): DataObjectModel;

    /**
     * @throws ForbiddenException|InvalidQueryTypeException|NotFoundException|UserNotFoundException
     */
    public function setTreeSorting(
        DataObjectModel $parent,
        QueryInterface $dataObjectQuery,
        bool $includeParent
    ): void;
}
