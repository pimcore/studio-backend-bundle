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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException as ApiNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Tool\Email\Blocklist;
use Pimcore\Model\Tool\Email\Blocklist\Listing;
use function sprintf;

/**
 * @internal
 */
final readonly class BlocklistRepository implements BlocklistRepositoryInterface
{
    /**
     * @throws EnvironmentException
     */
    public function addEntry(string $email): void
    {
        try {
            $this->getEntry($email);
        } catch (NotFoundException) {
            $this->createNewEntry($email);
        }
    }

    public function getListing(
        CollectionParameters $parameters,
        string $email = null,
    ): Listing {
        $limit = $parameters->getPageSize();
        $listing = new Listing();
        $listing->setLimit($limit);
        $listing->setOffset(($parameters->getPage() - 1) * $limit);

        if ($email !== null) {
            $listing->setCondition('`address` LIKE ' . $listing->quote('%'. $email .'%'));
        }

        return $listing;
    }

    /**
     * @throws ApiNotFoundException
     */
    public function deleteEntry(string $email): void
    {
        $blockList = $this->getExistingEntry($email);
        $blockList->delete();
    }

    private function createNewEntry(string $email): void
    {
        try {
            $blockList = new Blocklist();
            $blockList->setAddress($email);
            $blockList->setCreationDate(time());
            $blockList->save();
        } catch (Exception $exception) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to add email to blocklist: %s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @throws ApiNotFoundException
     */
    private function getExistingEntry(string $email): Blocklist
    {
        try {
            return $this->getEntry($email);
        } catch (NotFoundException) {
            throw new ApiNotFoundException(
                type: 'blocklist entry',
                id: $email,
                parameter: 'email address'
            );
        }
    }

    /**
     * @throws NotFoundException
     */
    private function getEntry(string $email): Blocklist
    {
        $blockList = new Blocklist();
        $blockList->getDao()->getByAddress($email);

        return $blockList;
    }
}
