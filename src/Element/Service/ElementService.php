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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Request\PathParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class ElementService implements ElementServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getElementIdByPath(
        string $elementType,
        PathParameter $pathParameter,
        UserInterface $user
    ): int {

        return $this->getAllowedElementByPath($elementType, $pathParameter->getPath(), $user)->getId();
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getAllowedElementById(
        string $elementType,
        int $elementId,
        UserInterface $user,
    ): ElementInterface {
        $element = $this->getElement($this->serviceResolver, $elementType, $elementId);
        $this->securityService->hasElementPermission($element, $user, ElementPermissions::VIEW_PERMISSION);

        return $element;
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getAllowedElementByPath(
        string $elementType,
        string $elementPath,
        UserInterface $user
    ): ElementInterface {
        $element = $this->getElementByPath($this->serviceResolver, $elementType, $elementPath);
        $this->securityService->hasElementPermission($element, $user, ElementPermissions::VIEW_PERMISSION);

        return $element;
    }

    public function hasElementDependencies(
        ElementInterface $element
    ): bool {
        if (($element instanceof Asset ||
            $element instanceof Document ||
            $element instanceof DataObject) &&
            $element->hasChildren()) {
            return true;
        }

        return $element->getDependencies()->isRequired();
    }
}
