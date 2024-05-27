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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Request\CollectionParametersInterface;

/**
 * @internal
 */
interface OpenSearchFilterInterface
{
    public const SERVICE_TYPE = 'open_search_filter';

    /**
     * @throws InvalidQueryTypeException|InvalidFilterTypeException
     */
    public function applyFilters(CollectionParametersInterface $parameters, string $type): QueryInterface;
}
