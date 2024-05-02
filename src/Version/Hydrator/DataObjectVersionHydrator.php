<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Version\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Model\DataObject;

/**
 * @internal
 */
final class DataObjectVersionHydrator implements DataObjectVersionHydratorInterface
{
    public function hydrate(
        DataObject $dataObject
    ): DataObjectVersion
    {
        $published = false;
        if ($dataObject instanceof DataObject\Concrete) {
            $published = $dataObject->isPublished();
        }
        return new DataObjectVersion(
            $dataObject->getModificationDate(),
            $dataObject->getRealFullPath(),
            $published,
        );
    }
}