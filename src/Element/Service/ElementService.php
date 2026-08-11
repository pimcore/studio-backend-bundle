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

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResolve\ElementResolveEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResponse\ElementSubtypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Request\PathParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Request\SearchTermParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Subtype;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderPaths;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
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
     * {@inheritdoc}
     */
    public function getElementIdByPath(
        string $elementType,
        PathParameter $pathParameter,
        UserInterface $user
    ): int {

        return $this->getElementByPath($this->serviceResolver, $elementType, $pathParameter->getPath())->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedElementIdByPath(
        string $elementType,
        PathParameter $pathParameter,
        UserInterface $user
    ): int {

        return $this->getAllowedElementByPath($elementType, $pathParameter->getPath(), $user)->getId();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getElementById(string $elementType, int $elementId): ElementInterface
    {
        return $this->getElement($this->serviceResolver, $elementType, $elementId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedElementByPath(
        string $elementType,
        string $elementPath,
        UserInterface $user
    ): ElementInterface {
        $element = $this->getElementByPath($this->serviceResolver, $elementType, $elementPath);

        // The root is used as a traversal anchor by tree operations. Descendant workspaces are not
        // relevant to "/" itself, so users with access only to descendants cannot pass the root check.
        // ElementTreeWidgetConfigHydrator::isDefaultRootFolder() already skips this lookup.
        if ($elementPath !== ElementFolderPaths::ROOT->value) {
            $this->securityService->hasElementPermission($element, $user, ElementPermissions::VIEW_PERMISSION);
        }

        return $element;
    }

    public function hasElementChildren(ElementInterface $element): bool
    {
        return ($element instanceof Asset || $element instanceof Document || $element instanceof DataObject) &&
            $element->hasChildren();
    }

    public function hasElementDependencies(ElementInterface $element): bool
    {
        if ($this->hasElementChildren($element)) {
            return true;
        }

        return $element->getDependencies()->isRequired();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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

    /**
     * @throws NotFoundException
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
            throw new NotFoundException('Subtype for Element', $element->getId());
        }

        return $subtype;
    }
}
