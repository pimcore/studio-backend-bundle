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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Filter;

use Pimcore\Bundle\StudioApiBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioApiBundle\Request\Query\Filter\CollectionParametersInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\QueryInterface;

/**
 * @internal
 */
interface OpenSearchFilterInterface
{
    public const SERVICE_TYPE = 'open_search_filter';

    public const TYPE_DATA_OBJECT = 'dataObject';

    public const TYPE_ASSET = 'asset';

    public const TYPE_DOCUMENT = 'document';

    /**
     * @throws InvalidQueryTypeException
     */
    public function applyFilters(CollectionParametersInterface $parameters, string $type): QueryInterface;
}
