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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Util\Trait;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\IdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\PathFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
trait SearchTermTrait
{
    // TODO: once documents are fully implemented in GDI we need to change the path filter to url filter for documents
    private function applySearchTerm(QueryInterface $query, string $searchTerm, ?UserInterface $user): QueryInterface
    {
        $query->setPageSize(1);

        if ($user !== null) {
            $query->setUser($user);
        }

        $filter = match(true) {
            !is_numeric($searchTerm) => $this->getPathFilter($searchTerm),
            default => new IdFilter((int)$searchTerm)
        };

        $query->getSearch()->addModifier($filter);

        return $query;
    }

    private function getPathFilter(string $searchTerm): PathFilter
    {
        // add a leading slash if it is missing to the search term
        // include the parent since we actually want the parent item
        $searchTerm = '/' . ltrim($searchTerm, '/');

        return new PathFilter($searchTerm, includeParentItem: true);
    }

    private function getNotFoundException(string $type, string $searchTerm): NotFoundException
    {
        $parameter = 'ID';
        if (!is_numeric($searchTerm)) {
            $parameter = 'Path';
        }

        return new NotFoundException($type, $searchTerm, $parameter);
    }
}
