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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Note\Event\NoteEvent;
use Pimcore\Bundle\StudioBackendBundle\Note\Hydrator\NoteHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Repository\NoteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Note\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\CreateNote;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\Note;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class NoteService implements NoteServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private NoteRepositoryInterface $noteRepository,
        private NoteHydratorInterface $noteHydrator,
        private ParameterBagInterface $parameterBag,
        private SecurityServiceInterface $securityService,
        private array $noteTypes
    ) {
    }

    private const DATA_OBJECT_NOTE_TYPES = 'pimcore_admin.dataObjects.notes_events.types';

    private const ASSET_NOTE_TYPES = 'pimcore_admin.assets.notes_events.types';

    private const DOCUMENT_NOTE_TYPES = 'pimcore_admin.documents.notes_events.types';

    /**
     * @throws ElementSavingFailedException|NotFoundException|UserNotFoundException
     */
    public function createNote(NoteElementParameters $noteElement, CreateNote $createNote): Note
    {
        $note = $this->noteRepository->createNote(
            $noteElement,
            $createNote,
            $this->securityService->getCurrentUser()
        );

        return $this->getNote($note->getId());
    }

    /**
     * @throws InvalidFilterException
     */
    public function listNotes(NoteElementParameters $noteElement, NoteParameters $parameters): Collection
    {
        $noteListing = $this->noteRepository->listNotes($noteElement, $parameters);
        $notes = [];
        foreach ($noteListing as $note) {

            $note = $this->noteHydrator->hydrate($note);

            $this->eventDispatcher->dispatch(
                new NoteEvent($note),
                NoteEvent::EVENT_NAME
            );

            $notes[] = $note;
        }

        return new Collection(
            $notes,
            $parameters->getPage(),
            $parameters->getPageSize(),
            $noteListing->getTotalCount()
        );
    }

    /**
     * @throws NotFoundException
     */
    public function deleteNote(int $id): void
    {
        $this->noteRepository->deleteNote($id);
    }

    /**
     * @throws NotFoundException
     */
    public function getNoteTypes(string $elementType): array
    {
        if (!isset($this->noteTypes[$elementType])) {
            throw new NotFoundException('Note type', $elementType, 'element type');
        }
        $noteTypes = $this->noteTypes[$elementType];

        try {
            $parameters = array_filter($this->parameterBag->get($this->getNoteTypeParameters($elementType)));
        } catch (ParameterNotFoundException) {
            return $noteTypes;
        }

        return array_values(array_unique(array_merge($parameters, $noteTypes)));
    }

    /**
     * @throws NotFoundException
     */
    private function getNote(int $id): Note
    {
        $note = $this->noteHydrator->hydrate(
            $this->noteRepository->getNote($id)
        );

        $this->eventDispatcher->dispatch(
            new NoteEvent($note),
            NoteEvent::EVENT_NAME
        );

        return $note;
    }

    /**
     * @throws NotFoundException
     */
    private function getNoteTypeParameters(string $elementType): string
    {
        return match ($elementType) {
            ElementTypes::TYPE_DATA_OBJECT => self::DATA_OBJECT_NOTE_TYPES,
            ElementTypes::TYPE_ASSET => self::ASSET_NOTE_TYPES,
            ElementTypes::TYPE_DOCUMENT => self::DOCUMENT_NOTE_TYPES,
            default => throw new NotFoundException('Note type', $elementType, 'element type'),
        };
    }
}
