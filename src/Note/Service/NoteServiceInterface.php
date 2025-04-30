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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\CreateNote;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\Note;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\NoteTypeCollection;

/**
 * @internal
 */
interface NoteServiceInterface
{
    /**
     * @throws ElementSavingFailedException|NotFoundException|UserNotFoundException
     */
    public function createNote(NoteElementParameters $noteElement, CreateNote $createNote): Note;

    /**
     * @throws InvalidFilterException
     */
    public function listNotes(NoteElementParameters $noteElement, NoteParameters $parameters): Collection;

    /**
     * @throws NotFoundException
     */
    public function deleteNote(int $id): void;

    /**
     * @throws NotFoundException
     */
    public function listNoteTypes(string $elementType): NoteTypeCollection;
}
