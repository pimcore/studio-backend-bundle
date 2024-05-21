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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Version\Event\DocumentVersionEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;
use Pimcore\Model\Document;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class DocumentVersionHydrator
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function hydrate(
        Document $document
    ): DocumentVersion {
        $hydratedDocument = new DocumentVersion(
            $document->getModificationDate(),
            $document->getRealFullPath(),
            $document->isPublished(),
        );

        $this->eventDispatcher->dispatch(
            new DocumentVersionEvent($hydratedDocument),
            DocumentVersionEvent::EVENT_NAME
        );

        return $hydratedDocument;
    }
}
