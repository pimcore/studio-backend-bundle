<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Email\Service;

use Pimcore\Bundle\StudioBackendBundle\Email\Event\PreResponse\BlocklistEntryEvent;
use Pimcore\Bundle\StudioBackendBundle\Email\Repository\BlocklistRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\BlocklistEntry;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException as ApiNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class BlocklistService implements BlocklistServiceInterface
{
    public function __construct(
        private BlocklistRepositoryInterface $blocklistRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws EnvironmentException
     */
    public function addEntry(string $email): void
    {
        $this->blocklistRepository->addEntry($email);
    }

    public function listEntries(
        CollectionParameters $parameters,
        string $email = null,
    ): Collection {
        $list = [];
        $listing = $this->blocklistRepository->getListing($parameters, $email);
        foreach ($listing as $listEntry) {
            $entry = new BlocklistEntry(
                $listEntry->getAddress(),
                $listEntry->getCreationDate(),
                $listEntry->getModificationDate()
            );

            $this->eventDispatcher->dispatch(
                new BlocklistEntryEvent($entry)
            );

            $list[] = $entry;
        }

        return new Collection(
            $listing->getTotalCount(),
            $list
        );
    }

    /**
     * @throws ApiNotFoundException
     */
    public function deleteEntry(string $email): void
    {
        $this->blocklistRepository->deleteEntry($email);
    }
}