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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidSearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ElementSearchServiceInterface
{
    public function getElementById(string $type, int $id, ?UserInterface $user = null): mixed;

    public function getChildrenIds(string $type, string $parentPath, ?string $sortDirection = null): array;

    /**
     * @throws InvalidElementTypeException|NotFoundException|SearchException
     */
    public function getElementBySearchTerm(string $type, string $searchTerm, ?UserInterface $user = null): int;

    /**
     * @throws InvalidElementTypeException|InvalidSearchException|SearchException
     */
    public function findElementInTree(string $type, int $id, QueryInterface $query): ?ElementSearchResultItemInterface;
}
