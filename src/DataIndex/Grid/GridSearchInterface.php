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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DocumentSearchResult;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface GridSearchInterface
{
    /**
     * @throws NotFoundException|SearchException
     */
    public function searchAssets(GridParameter $gridParameter): AssetSearchResult;

    public function searchAssetsForUser(GridParameter $gridParameter, UserInterface $user): AssetSearchResult;

    public function searchDataObjects(GridParameter $gridParameter): DataObjectSearchResult;

    public function searchDocuments(GridParameter $gridParameter): DocumentSearchResult;

    /**
     * @throws InvalidQueryTypeException
     */
    public function searchElementsForUser(
        string $type,
        GridParameter $gridParameter,
        UserInterface $user
    ): AssetSearchResult|DataObjectSearchResult|DocumentSearchResult;

    /**
     * @throws InvalidQueryTypeException
     */
    public function searchElementIdsForUser(
        string $type,
        GridParameter $gridParameter,
        UserInterface $user
    ): array;
}
