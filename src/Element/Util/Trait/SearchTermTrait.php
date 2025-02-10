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
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
trait SearchTermTrait
{
    private function applySearchTerm(QueryInterface $query, string $searchTerm, ?UserInterface $user): QueryInterface
    {
        $query->setPageSize(1);

        if ($user !== null) {
            $query->setUser($user);
        }

        $search = $query->getSearch();
        $modifier =  match(true) {
            !is_numeric($searchTerm)  => new PathFilter($searchTerm, includeParentItem: true),
            default => new IdFilter((int)$searchTerm)
        };

        $search->addModifier($modifier);

        return $query;
    }
}
