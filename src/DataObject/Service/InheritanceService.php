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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final readonly class InheritanceService implements InheritanceServiceInterface
{
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
        array $fieldDefinitions
    ): array {

        return $this->dataObjectServiceResolver->useInheritedValues(
            false,
            function () use ($object, $fieldDefinitions) {
                $inheritanceData = [];
                if (!$object->getParent() instanceof Concrete) {
                    return $inheritanceData;
                }

                foreach ($fieldDefinitions as $key => $fieldDefinition) {
                    $inheritanceData['metaData'][$key] = $this->processFieldDefinition(
                        $object,
                        $fieldDefinition,
                        $key
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
        if ($fieldDefinition->supportsInheritance() === false) {
            // The only place the flag is turned off, and the field type itself is the
            // only thing that can turn it off - everything below takes part in
            // inheritance, so the other places that build the data keep the default.
            return new InheritanceData($object->getId(), inheritable: false);
        }

        // A field type without a data adapter is one Studio has no adapter registered
        // for; that says nothing about whether the field can inherit, so it keeps the
        // flag and falls through to the generic origin walk.
        $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());
        if ($adapter instanceof DataInheritanceInterface) {
            return $adapter->getFieldInheritance(
                $object,
                $fieldDefinition,
                $key,
                $contextData
            );
        }

        $originId = $this->getOriginId($object, $fieldDefinition, $key, $contextData);

        return new InheritanceData(
            $originId,
            $originId !== $object->getId()
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
        if (!$fieldDefinition->isEmpty($this->getValidFieldValue($object, $key, $contextData))) {
            return $object->getId();
        }

        $parent = $object->getNextParentForInheritance();
        if (!$parent) {
            return $object->getId();
        }

        return $this->getOriginId(
            $parent,
            $fieldDefinition,
            $key,
            $contextData?->getContextObjectFromElement($parent)
        );
    }
}
