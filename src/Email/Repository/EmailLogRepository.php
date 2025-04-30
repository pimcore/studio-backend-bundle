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

use Pimcore\Bundle\StaticResolverBundle\Models\Tool\EmailLogResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Model\Tool\Email\Log;
use Pimcore\Model\Tool\Email\Log\Listing;

/**
 * @internal
 */
final readonly class EmailLogRepository implements EmailLogRepositoryInterface
{
    private const DEFAULT_ORDER_KEY = 'sentDate';

    public function __construct(
        private EmailLogResolverInterface $emailLogResolver,
    ) {
    }

    public function getListing(
        CollectionParameters $parameters,
        ?string $email = null,
    ): Listing {
        $limit = $parameters->getPageSize();
        $listing = new Listing();
        $listing->setLimit($limit);
        $listing->setOffset(($parameters->getPage() - 1) * $limit);
        $listing->setOrderKey(self::DEFAULT_ORDER_KEY);
        $listing->setOrder('DESC');

        return $listing;
    }

    /**
     * @throws NotFoundException
     */
    public function deleteEntry(int $id): void
    {
        $blockList = $this->getExistingEntry($id);
        $blockList->delete();
    }

    /**
     * @throws NotFoundException
     */
    public function getExistingEntry(int $id): Log
    {
        $entry = $this->getEntry($id);

        if ($entry === null) {
            throw new NotFoundException(
                type: 'E-mail log entry',
                id: $id
            );
        }

        return $entry;
    }

    private function getEntry(int $id): ?Log
    {
        return $this->emailLogResolver->getById($id);
    }
}
