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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataInheritanceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceOrigin;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\DetailValueTrait;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final readonly class InheritanceService implements InheritanceServiceInterface
{
    use DetailValueTrait;
    use ValidateObjectDataTrait;

    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
        private DataObjectServiceResolverInterface $dataObjectServiceResolver
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function getInheritanceData(
        Concrete $object,
        array $fieldDefinitions,
        bool $resolveInheritedValues = false
    ): array {

        return $this->dataObjectServiceResolver->useInheritedValues(
            false,
            function () use ($object, $fieldDefinitions, $resolveInheritedValues) {
                $inheritanceData = [];
                if (!$object->getParent() instanceof Concrete) {
                    return $inheritanceData;
                }

                $contextData = new FieldContextData(resolveInheritedValue: $resolveInheritedValues);
                foreach ($fieldDefinitions as $key => $fieldDefinition) {
                    $inheritanceData['metaData'][$key] = $this->processFieldDefinition(
                        $object,
                        $fieldDefinition,
                        $key,
                        $contextData
                    );
                }

                return $inheritanceData;
            }
        );
    }

    /**
     * @throws NotFoundException
     */
    public function processFieldDefinition(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): array|InheritanceData {
        $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());

        if ($adapter === null || $fieldDefinition->supportsInheritance() === false) {
            return new InheritanceData($object->getId(), inheritable: false);
        }

        if ($adapter instanceof DataInheritanceInterface) {
            return $adapter->getFieldInheritance(
                $object,
                $fieldDefinition,
                $key,
                $contextData
            );
        }

        return $this->getFieldInheritanceData($object, $fieldDefinition, $key, $contextData);
    }

    /**
     * @throws NotFoundException
     */
    public function getFieldInheritanceData(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): InheritanceData {
        if (!$this->isEligibleForInheritance($fieldDefinition)) {
            return new InheritanceData($object->getId(), inheritable: false);
        }

        $origin = $this->findOrigin($object, $fieldDefinition, $key, $contextData);
        $inherited = $origin->getObject()->getId() !== $object->getId();
        $originId = $inherited ? $origin->getObject()->getId() : $object->getId();

        $inheritedValue = null;
        if ($contextData?->shouldResolveInheritedValue()) {
            $inheritedValue = $this->getInheritedValue(
                $object,
                $fieldDefinition,
                $key,
                $contextData,
                $inherited ? $origin : null
            );
        }

        return new InheritanceData($originId, $inherited, true, $inheritedValue);
    }

    /**
     * A field is only eligible for inheritance resolution when a data adapter is registered for its type
     * and the field itself supports inheritance. This mirrors the guard in processFieldDefinition() so
     * that callers reaching this leaf helper directly (e.g. for classification store keys) cannot bypass it.
     */
    private function isEligibleForInheritance(Data $fieldDefinition): bool
    {
        return $fieldDefinition->supportsInheritance()
            && $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType()) !== null;
    }

    /**
     * @param InheritanceOrigin|null $origin the already resolved ancestor origin of an inherited value
     *
     * @throws NotFoundException
     */
    private function getInheritedValue(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData,
        ?InheritanceOrigin $origin
    ): mixed {
        // an inherited value already comes from the nearest ancestor holding one, an own value hides it
        $origin ??= $this->findParentOrigin($object, $fieldDefinition, $key, $contextData);
        // findOrigin() may hand back the terminal ancestor with an empty value (see its own docblock);
        // that is not an inherited value, so it must not be resolved/normalized like one
        if ($origin === null || $fieldDefinition->isEmpty($origin->getValue())) {
            return null;
        }

        return $this->resolveDetailValue(
            $this->dataAdapterService,
            $origin->getObject(),
            $origin->getValue(),
            $fieldDefinition,
            $origin->getContextData()
        );
    }

    /**
     * @throws NotFoundException
     */
    public function getOriginId(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): int {
        return $this->findOrigin($object, $fieldDefinition, $key, $contextData)->getObject()->getId();
    }

    /**
     * Walks up from the object itself to the nearest ancestor holding a non-empty value. When none of
     * them does, the terminal ancestor is still reported (with an empty value) rather than null, so
     * that objectId/inherited keep resolving exactly like the previous, non-value-aware getOriginId().
     *
     * @throws NotFoundException
     */
    private function findOrigin(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): InheritanceOrigin {
        // a container (e.g. an object brick) missing on this ancestor is not the same as no container at
        // all: falling through to a same-named root field would read unrelated data, so treat it as empty
        // and keep walking instead - the container may still reappear further up the ancestor chain
        $value = $contextData?->isContainerContextUnresolved() === true
            ? null
            : $this->getValidFieldValue($object, $key, $contextData);

        if (!$fieldDefinition->isEmpty($value)) {
            return new InheritanceOrigin($object, $value, $contextData);
        }

        $parent = $object->getNextParentForInheritance();
        if (!$parent) {
            return new InheritanceOrigin($object, $value, $contextData);
        }

        return $this->findOrigin(
            $parent,
            $fieldDefinition,
            $key,
            $contextData?->getContextObjectFromElement($parent)
        );
    }

    /**
     * Walks up from the next parent for inheritance to the nearest ancestor holding a non-empty value,
     * ignoring the object's own value. Unlike findOrigin(), this returns null - not the terminal ancestor -
     * when no ancestor holds one, since it only backs the actual inherited value, which has no legacy
     * terminal-ancestor fallback to preserve.
     *
     * @throws NotFoundException
     */
    private function findParentOrigin(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): ?InheritanceOrigin {
        $parent = $object->getNextParentForInheritance();
        if (!$parent) {
            return null;
        }

        $origin = $this->findOrigin(
            $parent,
            $fieldDefinition,
            $key,
            $contextData?->getContextObjectFromElement($parent)
        );

        return $fieldDefinition->isEmpty($origin->getValue()) ? null : $origin;
    }
}
