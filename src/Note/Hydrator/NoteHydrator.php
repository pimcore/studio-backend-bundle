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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Note\Extractor\NoteDataExtractorInterface;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\Note;
use Pimcore\Model\Element\Note as CoreNote;

/**
 * @internal
 */
final readonly class NoteHydrator implements NoteHydratorInterface
{
    public function __construct(private NoteDataExtractorInterface $extractor)
    {
    }

    public function hydrate(CoreNote $note): Note
    {
        $noteUser = $this->extractor->extractUserData($note);

        return new Note(
            $note->getId(),
            $note->getType(),
            $note->getCid(),
            $note->getCtype(),
            $this->extractor->extractCPath($note),
            $note->getDate(),
            $note->getTitle(),
            $note->getDescription(),
            $note->getLocked(),
            $this->extractor->extractData($note),
            $noteUser->getId(),
            $noteUser->getName(),
        );
    }
}
