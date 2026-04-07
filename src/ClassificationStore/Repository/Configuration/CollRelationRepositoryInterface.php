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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation\Listing;

/**
 * @internal
 */
interface CollRelationRepositoryInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function getListing(FilterParameter $parameters, int $colId): Listing;

    /**
     * @throws ElementSavingFailedException
     */
    public function createOrUpdate(int $colId, int $groupId, int $sorter): CollectionGroupRelation;

    /**
     * @throws Exception
     */
    public function delete(int $colId, int $groupId): void;
}
