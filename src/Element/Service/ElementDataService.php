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
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Event\ElementEvents;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\FactoryInterface;
use Pimcore\Model\User;
use Pimcore\Resolver\ResolverInterface;
use Pimcore\Workflow\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function class_exists;
use function is_subclass_of;
use function strlen;
use function substr;
use function ucfirst;

/**
 * @internal
 */
final readonly class ElementDataService implements ElementSummaryDataServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private ResolverInterface $assetClassResolver,
        private ResolverInterface $documentClassResolver,
        private FactoryInterface $modelFactory,
        private Manager $workflowManager,
        private EventDispatcherInterface $eventDispatcher,
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

    /**
     * The view permission is checked on an unsaved model of the element's class that only carries the identity,
     * path and parent from the summary, which is all the core workspace check needs. Whenever something could
     * look at more than that - a workflow that applies to the element (its places may deny the permission and
     * are read from the element's data) or a listener on the permission event - the element is loaded and
     * checked as usual.
     *
     * {@inheritdoc}
     */
    public function getRelatedElementDataFromSummary(array $summary): RelatedElementData
    {
        $element = $this->createElementFromSummary($summary);
        if ($element === null || $this->needsLoadedElementForPermission($element)) {
            $element = $this->serviceResolver->getElementById($summary['type'], $summary['id']);
            if ($element === null) {
                return $this->createRelatedElementDataFromSummary($summary, false);
            }

            return $this->getRelatedElementData($element);
        }

        return $this->createRelatedElementDataFromSummary($summary, $this->hasViewAccess($element));
    }

    private function hasViewAccess(ElementInterface $element): bool
    {
        $user = $this->getCurrentUser();
        if ($user === null) {
            return false;
        }

        return $element->isAllowed(ElementPermissions::VIEW_PERMISSION, $user);
    }

    private function getCurrentUser(): ?User
    {
        try {
            $user = $this->securityService->getCurrentUser();
        } catch (UserNotFoundException) {
            return null;
        }

        return $user instanceof User ? $user : null;
    }

    private function needsLoadedElementForPermission(ElementInterface $element): bool
    {
        if ($this->eventDispatcher->hasListeners(ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED)) {
            return true;
        }

        foreach ($this->workflowManager->getAllWorkflows() as $workflowName) {
            $config = $this->workflowManager->getWorkflowConfig($workflowName)->getWorkflowConfigArray();
            if (!empty($config['supports'])) {
                foreach ((array) $config['supports'] as $supportedClass) {
                    if ($element instanceof $supportedClass) {
                        return true;
                    }
                }
            } elseif (isset($config['support_strategy'])) {
                // whether such a workflow applies may depend on data the model does not carry
                return true;
            }
        }

        return false;
    }

    private function createRelatedElementDataFromSummary(array $summary, bool $hasViewAccess): RelatedElementData
    {
        $isConcrete = $summary['type'] === ElementTypes::TYPE_OBJECT && $summary['className'] !== null;
        $isPublished = $isConcrete || $summary['type'] === ElementTypes::TYPE_DOCUMENT ? $summary['published'] : null;

        return new RelatedElementData(
            $summary['id'],
            $summary['type'],
            $summary['className'] ?? $summary['subtype'],
            $summary['fullpath'],
            $isPublished,
            $hasViewAccess
        );
    }

    private function createElementFromSummary(array $summary): ?ElementInterface
    {
        $className = $this->resolveElementClass($summary);
        if ($className === null) {
            return null;
        }

        $element = $this->modelFactory->build($className);
        if (!$element instanceof ElementInterface) {
            return null;
        }

        $filename = $summary['filename'];
        $path = $filename === '' ? $summary['fullpath'] : substr($summary['fullpath'], 0, -strlen($filename));
        $element->setId($summary['id']);
        $element->setParentId($summary['parentId']);
        $element->setPath($path);
        $element->setType($summary['subtype']);
        if ($element instanceof Asset) {
            $element->setFilename($filename);
        } else {
            $element->setKey($filename);
        }

        if ($element instanceof Concrete || $element instanceof Document) {
            $element->setPublished($summary['published']);
        }

        return $element;
    }

    /**
     * The same classes the core instantiates when loading the element, so model overrides apply.
     */
    private function resolveElementClass(array $summary): ?string
    {
        return match ($summary['type']) {
            ElementTypes::TYPE_OBJECT => $this->resolveObjectClass($summary['className']),
            ElementTypes::TYPE_ASSET => $this->assetClassResolver->resolve($summary['subtype']),
            ElementTypes::TYPE_DOCUMENT => $this->documentClassResolver->resolve($summary['subtype']),
            default => null,
        };
    }

    private function resolveObjectClass(?string $className): ?string
    {
        if ($className === null) {
            return Folder::class;
        }

        $class = 'Pimcore\\Model\\DataObject\\' . ucfirst($className);

        return class_exists($class) && is_subclass_of($class, Concrete::class) ? $class : null;
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
