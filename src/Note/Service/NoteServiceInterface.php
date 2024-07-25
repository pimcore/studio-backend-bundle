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
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\CreateNote;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\Note;

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
}
