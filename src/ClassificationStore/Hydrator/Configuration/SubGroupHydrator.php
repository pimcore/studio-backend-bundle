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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupDetail;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;

/**
 * @internal
 */
final readonly class SubGroupHydrator implements SubGroupHydratorInterface
{
    public function hydrateSubGroupDetail(SubGroupConfig $subGroupConfig): SubGroupDetail
    {
        return new SubGroupDetail(
            $subGroupConfig->getId(),
            $subGroupConfig->getName(),
            $subGroupConfig->getStoreId(),
            $subGroupConfig->getIdentifier(),
            $subGroupConfig->getDescription(),
            $subGroupConfig->getCreationDate(),
            $subGroupConfig->getModificationDate(),
        );
    }
}
