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

use Pimcore\Bundle\StudioBackendBundle\Version\Event\DataObjectVersionEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Model\DataObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class DataObjectVersionHydrator implements DataObjectVersionHydratorInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function hydrate(
        DataObject $dataObject
    ): DataObjectVersion {
        $published = false;
        if ($dataObject instanceof DataObject\Concrete) {
            $published = $dataObject->isPublished();
        }

        $hydratedDataObject =  new DataObjectVersion(
            $dataObject->getModificationDate(),
            $dataObject->getRealFullPath(),
            $published,
        );

        $this->eventDispatcher->dispatch(
            new DataObjectVersionEvent($hydratedDataObject),
            DataObjectVersionEvent::EVENT_NAME
        );

        return $hydratedDataObject;
    }
}
