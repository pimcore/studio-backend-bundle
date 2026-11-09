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

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\ClassificationStoreFilterValue;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * The group, key and value a classification store column filter searches with.
 *
 * @internal
 */
interface ClassificationStoreFilterValueResolverInterface
{
    /**
     * The identifiers of the filter value, when it carries both, are the reference and the integer pair is not
     * consulted. They are resolved in the store of the classification store field `$fieldName` of the filtered
     * class, which `$parameters` names when it carries a class id.
     *
     * @throws InvalidArgumentException when the value carries neither a resolvable reference nor a full pair
     * @throws NotFoundException when the identifiers do not resolve in this installation
     */
    public function resolve(
        array $rawFilterValue,
        mixed $parameters = null,
        string $fieldName = ''
    ): ClassificationStoreFilterValue;
}
