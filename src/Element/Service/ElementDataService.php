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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Service\Permission\ElementPermissionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;

/**
 * @internal
 */
final readonly class ElementDataService implements ElementDataServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private SecurityServiceInterface $securityService,
        private ElementPermissionServiceInterface $elementPermissionService,
    ) {
    }

    public function getRelatedElementData(ElementInterface $element): RelatedElementData
    {
        return new RelatedElementData(
            $element->getId(),
            $this->getElementType($element, true),
            $this->getSubType($element),
            $element->getRealFullPath(),
            $this->getPublished($element),
            $this->hasViewAccess($element)
        );
    }

    private function hasViewAccess(ElementInterface $element): bool
    {
        try {
            $user = $this->securityService->getCurrentUser();
        } catch (UserNotFoundException) {
            return false;
        }

        if (!$user instanceof User) {
            return false;
        }

        return $this->elementPermissionService->isAllowed(
            ElementPermissions::VIEW_PERMISSION,
            $element,
            $user
        );
    }

    private function getSubType(ElementInterface $element): string
    {
        if ($element instanceof Concrete) {
            return $element->getClassName();
        }

        return $element->getType();
    }

    private function getPublished(ElementInterface $element): ?bool
    {
        if ($element instanceof Concrete || $element instanceof Document) {
            return $element->getPublished();
        }

        return null;
    }
}
