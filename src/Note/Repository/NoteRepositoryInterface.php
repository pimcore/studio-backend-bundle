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

use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\CreateNote;
use Pimcore\Model\Element\Note;
use Pimcore\Model\Element\Note\Listing as NoteListing;

/**
 * @internal
 */
interface NoteRepositoryInterface
{
    /**
     * @throws ElementSavingFailedException
     */
    public function createNote(NoteElementParameters $noteElement, CreateNote $createNote): Note;

    /**
     * @throws ElementNotFoundException
     */
    public function getNote(int $id): ?Note;

    /**
     * @throws InvalidFilterException
     */
    public function listNotes(NoteElementParameters $noteElement, NoteParameters $parameters): NoteListing;

    /**
     * @throws ElementNotFoundException
     */
    public function deleteNote(int $id): void;
}
