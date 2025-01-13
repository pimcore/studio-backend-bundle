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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataInheritanceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateFieldTypeTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final readonly class InheritanceService implements InheritanceServiceInterface
{
    use ValidateFieldTypeTrait;

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
    ): array
    {

        return $this->dataObjectServiceResolver->useInheritedValues(
            false,
            function () use ($object, $fieldDefinitions) {
                $inheritanceData = [];
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
        $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());

        if ($adapter === null || $fieldDefinition->supportsInheritance() === false) {
            return new InheritanceData($object->getId());
        }

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
    private function getOriginId(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): int {
        if (!$fieldDefinition->isEmpty($this->getFieldValue($object, $fieldDefinition, $key, $contextData))) {
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
            $contextData
        );
    }

    private function getFieldValue(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): mixed
    {
        $fieldKey = $this->getFieldKeyByContext(
            $key,
            $fieldDefinition,
            $contextData
        );
        $value = $this->getValidFieldValue($object, $fieldKey, $contextData?->getLanguage());

        if ($contextData && $contextData->getContextObject()) {
            $value = $contextData->getBrickValueFromElement($value, $fieldDefinition->getName());
        }

        return $value;
    }

    private function getFieldKeyByContext(
        string $key,
        Data $fieldDefinition,
        ?FieldContextData $contextData = null
    ): string {
        if ($contextData === null) {
            return $key;
        }

        if ($contextData->getLanguage() && $contextData->getContextObject()) {
            return $key;
        }

        return $fieldDefinition->getName();
    }
}
