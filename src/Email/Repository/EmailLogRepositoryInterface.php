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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Model\Tool\Email\Log;
use Pimcore\Model\Tool\Email\Log\Listing;

/**
 * @internal
 */
interface EmailLogRepositoryInterface
{
    public function getListing(
        CollectionParameters $parameters,
        ?string $email = null,
    ): Listing;

    public function getFilteredListing(FilterParameter $filter): Listing;

    /**
     * @throws NotFoundException
     */
    public function deleteEntry(int $id): void;

    /**
     * @throws NotFoundException
     */
    public function getExistingEntry(int $id): Log;
}
