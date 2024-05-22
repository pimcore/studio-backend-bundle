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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Note\Hydrator\NoteHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Note\Repository\NoteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Note\Request\NoteElement;
use Pimcore\Bundle\StudioBackendBundle\Note\Request\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\CreateNote;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\Note;

/**
 * @internal
 */
final readonly class NoteService implements NoteServiceInterface
{

    public function __construct(
        private NoteRepositoryInterface $noteRepository,
        private NoteHydratorInterface $noteHydrator
    )
    {
    }

    /**
     * @throws ElementSavingFailedException
     */
    public function createNote(NoteElement $noteElement, CreateNote $createNote): Note
    {
        $note = $this->noteRepository->createNote($noteElement, $createNote);
        return $this->getNote($note->getId());
    }

    public function listNotes(NoteElement $noteElement, NoteParameters $parameters): Collection
    {
        $noteListing = $this->noteRepository->listNotes($noteElement, $parameters);
        $notes = [];
        foreach ($noteListing as $note) {
            $notes[] = $this->noteHydrator->hydrate($note);
        }

        return new Collection(
            $notes,
            $parameters->getPage(),
            $parameters->getPageSize(),
            $noteListing->getTotalCount()
        );
    }

    /**
     * @throws ElementNotFoundException
     */
    public function deleteNote(int $id): void
    {
        $this->noteRepository->deleteNote($id);
    }

    private function getNote(int $id): Note
    {
        return $this->noteHydrator->hydrate($this->noteRepository->getNote($id));
    }
}