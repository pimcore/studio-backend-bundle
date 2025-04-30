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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException as ApiNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Model\Tool\Email\Blocklist\Listing;

/**
 * @internal
 */
interface BlocklistRepositoryInterface
{
    /**
     * @throws EnvironmentException
     */
    public function addEntry(string $email): void;

    public function getListing(
        CollectionParameters $parameters,
        ?string $email = null,
    ): Listing;

    /**
     * @throws ApiNotFoundException
     */
    public function deleteEntry(string $email): void;
}
