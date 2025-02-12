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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResolve\ElementResolveEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResponse\ElementContextPermissionsEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResponse\ElementSubtypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Request\PathParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Request\SearchTermParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\AssetContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DataObjectContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DocumentContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Subtype;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException as ApiNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class ElementService implements ElementServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ElementSearchServiceInterface $elementSearchService,
        private EventDispatcherInterface $eventDispatcher,
        private ServiceResolverInterface $serviceResolver,
        private SecurityServiceInterface $securityService,

    ) {
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getElementIdByPath(
        string $elementType,
        PathParameter $pathParameter,
        UserInterface $user
    ): int {

        return $this->getAllowedElementByPath($elementType, $pathParameter->getPath(), $user)->getId();
    }

    /**
     * @throws ForbiddenException|NotFoundException
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
     * @throws ForbiddenException|NotFoundException
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

    /**
     * @throws ApiNotFoundException|ForbiddenException|UserNotFoundException
     */
    public function getElementSubtype(ElementParameters $parameters): Subtype
    {
        $user = $this->securityService->getCurrentUser();
        $element = $this->getAllowedElementById($parameters->getType(), $parameters->getId(), $user);

        $subtype = new Subtype($parameters->getId(), $parameters->getType(), $this->getSubtypeFromElement($element));
        $this->eventDispatcher->dispatch(new ElementSubtypeEvent($subtype), ElementSubtypeEvent::EVENT_NAME);

        return $subtype;
    }

    /**
     * @throws InvalidElementTypeException
     */
    public function getElementContextPermissions(
        string $elementType
    ): AssetContextPermissions|DataObjectContextPermissions|DocumentContextPermissions {
        $permissions = match ($elementType) {
            ElementTypes::TYPE_ASSET => new AssetContextPermissions(),
            ElementTypes::TYPE_DATA_OBJECT => new DataObjectContextPermissions(),
            ElementTypes::TYPE_DOCUMENT => new DocumentContextPermissions(),
            default => throw new InvalidElementTypeException($elementType),
        };

        $this->eventDispatcher->dispatch(
            new ElementContextPermissionsEvent($permissions),
            ElementContextPermissionsEvent::EVENT_NAME
        );

        return $permissions;
    }

    /**
     * @throws ApiNotFoundException
     */
    private function getSubtypeFromElement(ElementInterface $element): string
    {
        $subtype = match (true) {
            $element instanceof Asset, $element instanceof Document => $element->getType(),
            $element instanceof DataObject\Concrete => $element->getClassName(),
            $element instanceof DataObject\Folder => ElementTypes::TYPE_FOLDER,
            default => null,
        };

        if ($subtype === null) {
            throw new ApiNotFoundException('Subtype for Element', $element->getId());
        }

        return $subtype;
    }

    /**
     * @throws InvalidElementTypeException|NotFoundException|SearchException
     */
    public function resolveBySearchTerm(string $elementType, SearchTermParameter $searchTerm, UserInterface $user): int
    {
        $event = $this->eventDispatcher->dispatch(
            new ElementResolveEvent($elementType, $searchTerm->getSearchTerm()),
            ElementResolveEvent::EVENT_NAME
        );

        $modifiedSearchTerm = $event->getSearchTerm();

        return $this->elementSearchService->getElementBySearchTerm($elementType, $modifiedSearchTerm, $user);
    }
}
