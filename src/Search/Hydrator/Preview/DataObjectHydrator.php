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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\Preview;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DataObjectSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
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
        private SecurityServiceInterface $securityService,
        private UserServiceInterface $userService,
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
            $this->hydratePreviewDetailData($dataObject),
        );
    }

    private function hydratePreviewDetailData(DataObject $dataObject): array
    {
        $version = $this->getLatestVersionForUser($dataObject, $this->securityService->getCurrentUser());
        $versionData = $this->getVersionData($dataObject, $version);

        if (!$versionData instanceof Concrete) {
            return [];
        }

        return $this->dataService->getPreviewObjectData($versionData);
    }

    private function getClassData(DataObject $dataObject): ?string
    {
        if (!$dataObject instanceof Concrete) {
            return null;
        }

        return sprintf('%s [%s]', $dataObject->getClassName(), $dataObject->getClassId());
    }
}
