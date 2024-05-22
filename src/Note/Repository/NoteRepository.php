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

use Pimcore\Bundle\StaticResolverBundle\Models\Element\NoteResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Note\Request\NoteElement;
use Pimcore\Bundle\StudioBackendBundle\Note\Request\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\CreateNote;
use Pimcore\Model\Element\Note;
use Pimcore\Model\Element\Note\Listing as NoteListing;

/**
 * @internal
 */
final readonly class NoteRepository implements NoteRepositoryInterface
{
    public function __construct(private NoteResolverInterface $noteResolver)
    {
    }

    public function createNote(NoteElement $noteElement, CreateNote $createNote): Note
    {
        $note = new Note();
        $note->setCid($noteElement->getId());
        $note->setCtype($noteElement->getType());
        $note->setDate(time());
        $note->setTitle($createNote->getTitle());
        $note->setDescription($createNote->getDescription());
        $note->setType($createNote->getType());
        $note->setLocked(false);
        $note->save();

        return $note;
    }

    public function getNote(int $id): Note
    {
        return $this->noteResolver->getById($id);
    }

    public function listNotes(NoteElement $noteElement, NoteParameters $parameters): NoteListing
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

        if ($parameters->getFilter()) {
            $condition = '('
                . '`title` LIKE :filter'
                . ' OR `description` LIKE :filter'
                . ' OR `type` LIKE :filter'
                . ' OR `user` IN (SELECT `id` FROM `users` WHERE `name` LIKE :filter)'
                . " OR DATE_FORMAT(FROM_UNIXTIME(`date`), '%Y-%m-%d') LIKE :filter"
                . ')';
            $list->addConditionParam($condition, ['filter' => '%' . $parameters->getFilter() . '%']);
        }

        if ($noteElement->getId() && $noteElement->getType()) {
            $list->addConditionParam(
                '(cid = :id AND ctype = :type)',
                ['id' => $noteElement->getId(), 'type' => $noteElement->getType()]
            );
        }

        return $list;
    }

    /**
     * @throws ElementNotFoundException
     */
    public function deleteNote(int $id): void
    {
        $note = $this->noteResolver->getById($id);
        if(!$note) {
            throw new ElementNotFoundException($id, 'Note');
        }
        $note->delete();
    }
}
