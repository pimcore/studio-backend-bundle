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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final readonly class DataObjectVersionHydrator implements DataObjectVersionHydratorInterface
{
    public function __construct(
        private DataServiceInterface $dataService,
        private IconServiceInterface $iconService
    ) {
    }

    public function hydrate(
        DataObject $dataObject
    ): DataObjectVersion {
        $className = null;
        $published = null;
        if ($dataObject instanceof Concrete) {
            $className = $dataObject->getClassName();
            $published = $dataObject->isPublished();
        }

        $hydratedDataObject = new DataObjectVersion(
            $dataObject->getKey(),
            $dataObject->getType(),
            $dataObject->hasChildren(),
            $dataObject->getFullPath(),
            $dataObject->getIndex(),
            $dataObject->getId(),
            $dataObject->getParentId(),
            $dataObject->getPath(),
            $this->iconService->getIconForDataObject($dataObject),
            $dataObject->getUserOwner(),
            $dataObject->getUserModification(),
            $dataObject->getLocked(),
            $dataObject->isLocked(),
            $dataObject->getCreationDate(),
            $dataObject->getModificationDate(),
            $className,
            $published
        );

        $this->dataService->setObjectDetailData($hydratedDataObject, $dataObject);

        return $hydratedDataObject;
    }
}
