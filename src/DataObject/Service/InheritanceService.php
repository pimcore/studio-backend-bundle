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
        $origin = $this->findOrigin($object, $fieldDefinition, $key, $contextData);
        $inherited = $origin !== null && $origin->getObject()->getId() !== $object->getId();

        return new InheritanceData(
            $inherited ? $origin->getObject()->getId() : $object->getId(),
            $inherited,
            true,
            $contextData?->shouldResolveInheritedValue()
                ? $this->getInheritedValue($object, $fieldDefinition, $key, $contextData, $inherited ? $origin : null)
                : null
        );
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
        if ($origin === null) {
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
        return $this->findOrigin($object, $fieldDefinition, $key, $contextData)?->getObject()->getId()
            ?? $object->getId();
    }

    /**
     * Walks up from the object itself to the nearest object holding a non-empty value.
     *
     * @throws NotFoundException
     */
    private function findOrigin(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): ?InheritanceOrigin {
        $value = $this->getValidFieldValue($object, $key, $contextData);
        if (!$fieldDefinition->isEmpty($value)) {
            return new InheritanceOrigin($object, $value, $contextData);
        }

        return $this->findParentOrigin($object, $fieldDefinition, $key, $contextData);
    }

    /**
     * Walks up from the next parent for inheritance to the nearest ancestor holding a non-empty value.
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

        return $this->findOrigin(
            $parent,
            $fieldDefinition,
            $key,
            $contextData?->getContextObjectFromElement($parent)
        );
    }
}
