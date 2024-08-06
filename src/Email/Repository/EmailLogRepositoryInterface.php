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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
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
        string $email = null,
    ): Listing;

    /**
     * @throws NotFoundException
     */
    public function deleteEntry(int $id): void;

    /**
     * @throws NotFoundException
     */
    public function getExistingEntry(int $id): Log;
}
