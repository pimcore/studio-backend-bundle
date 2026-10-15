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
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\FactoryInterface;
use Pimcore\Model\User;
use Pimcore\Resolver\ResolverInterface;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function class_exists;
use function is_string;
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
        private EventDispatcherInterface $eventDispatcher,
        /** @var array<string, array<string, mixed>> the workflow configuration, keyed by workflow name */
        private array $workflowConfigs,
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
     * are read from the element's data), a listener on the permission event or a class of the project in the
     * model's hierarchy - the element is loaded and checked as usual.
     *
     * {@inheritdoc}
     */
    public function getRelatedElementDataFromSummary(array $summary): RelatedElementData
    {
        $className = $this->resolveElementClass($summary);
        $element = $className === null ? null : $this->createElementFromSummary($summary, $className);
        if ($element === null || $this->needsLoadedElementForPermission($element, $className)) {
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

    private function needsLoadedElementForPermission(ElementInterface $element, string $className): bool
    {
        if (!$this->isPermissionDecidedByCore($className)) {
            return true;
        }

        foreach ($this->workflowConfigs as $config) {
            if ($this->workflowMayApply($config, $element)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A listener on the permission event or a class of the project in the model's hierarchy (a model override,
     * a custom type class or a custom parent class) may decide with data the model does not carry; only the
     * core implementation is known to need nothing but identity, path and parent.
     */
    private function isPermissionDecidedByCore(string $className): bool
    {
        if ($this->eventDispatcher->hasListeners(ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED)) {
            return false;
        }

        if (isset($this->modelFactory->getClassMap()[$className])) {
            return false;
        }

        $declaringClass = (new ReflectionMethod($className, 'isAllowed'))->getDeclaringClass()->getName();

        return $declaringClass === AbstractElement::class;
    }

    private function workflowMayApply(array $config, ElementInterface $element): bool
    {
        if (($config['enabled'] ?? true) === false) {
            return false;
        }

        if (!empty($config['supports'])) {
            foreach ((array) $config['supports'] as $supportedClass) {
                if ($element instanceof $supportedClass) {
                    return true;
                }
            }

            return false;
        }

        return isset($config['support_strategy'])
            && $this->supportStrategyMayApply($config['support_strategy'], $element);
    }

    /**
     * An expression strategy only applies to instances of the classes in its first argument; whether its
     * expression holds may depend on data the model does not carry. Any other strategy cannot be judged.
     */
    private function supportStrategyMayApply(array $strategy, ElementInterface $element): bool
    {
        if (($strategy['type'] ?? null) !== 'expression') {
            return true;
        }

        $classes = (array) ($strategy['arguments'][0] ?? []);
        if ($classes === []) {
            return true;
        }

        foreach ($classes as $class) {
            if (is_string($class) && $element instanceof $class) {
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

    private function createElementFromSummary(array $summary, string $className): ?ElementInterface
    {
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
