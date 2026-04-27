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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;

/**
 * @internal
 */
interface SearchIndexFilterInterface extends FilterServiceInterface
{
    public const string SERVICE_TYPE = 'search_index_filter';

    /**
     * @template T of QueryInterface
     *
     * @param T $query
     *
     * @return T
     *
     * @throws InvalidFilterTypeException
     */
    public function applyFilters(
        QueryInterface $query,
        CollectionParametersInterface $parameters,
        string $type
    ): QueryInterface;
}
