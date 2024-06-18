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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Repository;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\NoteResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\CreateNote;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\FilterServiceInterface;
use Pimcore\Model\Element\Note;
use Pimcore\Model\Element\Note\Listing as NoteListing;

/**
 * @internal
 */
final readonly class NoteRepository implements NoteRepositoryInterface
{
    public function __construct(
        private NoteResolverInterface $noteResolver,
        private FilterServiceInterface $filterService
    ) {
    }

    /**
     * @throws ElementSavingFailedException
     */
    public function createNote(NoteElementParameters $noteElement, CreateNote $createNote): Note
    {
        $note = new Note();
        $note->setCid($noteElement->getId());
        $note->setCtype($noteElement->getType());
        $note->setDate(time());
        $note->setTitle($createNote->getTitle());
        $note->setDescription($createNote->getDescription());
        $note->setType($createNote->getType());
        $note->setLocked(false);

        try {
            $note->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(0, $e->getTraceAsString());
        }

        return $note;
    }

    /**
     * @throws NotFoundException
     */
    public function getNote(int $id): Note
    {
        $note = $this->noteResolver->getById($id);

        if (!$note) {
            throw new NotFoundException('Note', $id);
        }

        return $note;
    }

    /**
     * @throws InvalidFilterException
     */
    public function listNotes(NoteElementParameters $noteElement, NoteParameters $parameters): NoteListing
    {
        $list = new NoteListing();

        $list->setOrderKey(['date', 'id']);
        $list->setOrder(['DESC', 'DESC']);

        $list->setLimit($parameters->getPageSize());
        $list->setOffset($parameters->getOffset());

        if ($parameters->getSortBy() && $parameters->getSortOrder()) {
            $list->setOrderKey($parameters->getSortBy());
            $list->setOrder($parameters->getSortOrder());
        }

        $this->filterService->applyFilter($list, $parameters);

        $this->filterService->applyFieldFilters($list, $parameters);

        $this->filterService->applyElementFilter($list, $noteElement);

        return $list;
    }

    /**
     * @throws NotFoundException
     */
    public function deleteNote(int $id): void
    {
        $note = $this->getNote($id);
        $note->delete();
    }
}
