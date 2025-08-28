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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
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
     * @throws ElementExistsException|EnvironmentException
     */
    public function addEntry(string $email): void
    {
        try {
            $existingEmail = $this->getEntry($email);

            throw new ElementExistsException(
                sprintf('Email (%s) is already in the blocklist.', $existingEmail->getAddress())
            );
        } catch (NotFoundException) {
            $this->createNewEntry($email);
        }
    }

    public function getListing(
        CollectionParameters $parameters,
        ?string $email = null,
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

    /**
     * @throws EnvironmentException
     */
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
