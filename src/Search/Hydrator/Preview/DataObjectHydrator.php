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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\Preview;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DataObjectSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use function sprintf;

/**
 * @internal
 */
final readonly class DataObjectHydrator implements DataObjectHydratorInterface
{
    use ElementProviderTrait;

    public function __construct(
        private DataServiceInterface $dataService,
        private UserServiceInterface $userService
    ) {
    }

    public function hydrate(DataObject $dataObject): DataObjectSearchPreview
    {
        return new DataObjectSearchPreview(
            $dataObject->getId(),
            $this->getElementType($dataObject),
            $dataObject->getType(),
            $dataObject->getUserOwner(),
            $dataObject->getUserOwner() !== null ?
                $this->userService->getUserNameById($dataObject->getUserOwner()) :
                null,
            $dataObject->getUserModification(),
            $dataObject->getUserModification() !== null ?
                $this->userService->getUserNameById($dataObject->getUserModification()) :
                null,
            $dataObject->getCreationDate(),
            $dataObject->getModificationDate(),
            $this->getClassData($dataObject),
            $this->dataService->getPreviewObjectData($dataObject),
        );
    }

    private function getClassData(DataObject $dataObject): ?string
    {
        if (!$dataObject instanceof Concrete) {
            return null;
        }

        return sprintf('%s [%s]', $dataObject->getClassName(), $dataObject->getClassId());
    }
}
