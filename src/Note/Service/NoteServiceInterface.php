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

use Pimcore\Bundle\StudioBackendBundle\Note\Request\NoteElement;
use Pimcore\Bundle\StudioBackendBundle\Note\Request\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\CreateNote;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\Note;

/**
 * @internal
 */
interface NoteServiceInterface
{
    public function createNote(NoteElement $noteElement, CreateNote $createNote): Note;

    public function listNotes(NoteElement $noteElement, NoteParameters $parameters): Collection;
}