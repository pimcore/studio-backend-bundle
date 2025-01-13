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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\ClassData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateFieldTypeTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final readonly class DataService implements DataServiceInterface
{
    use ValidateFieldTypeTrait;

    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
        private InheritanceServiceInterface $inheritanceService,
        private WorkflowDetailsServiceInterface $workflowDetailsService,
    ) {
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function setObjectDetailData(
        DataObjectFolder|DataObject $dataObject,
        Concrete $element,
        ClassDefinition $class
    ): void
    {
        $classData = $this->getObjectClassData($class);
        $fieldDefinitions = $class->getFieldDefinitions();

        $dataObject->setAllowInheritance($classData->getAllowInheritance());
        $dataObject->setAllowVariants($classData->getAllowVariants());
        $dataObject->setShowVariants($classData->getShowVariants());
        $dataObject->setHasPreview($classData->getHasPreview());
        $dataObject->setObjectData($this->getNormalizedObjectData($element, $fieldDefinitions));

        if ($dataObject->getAllowInheritance()) {
            $dataObject->setInheritanceData(
                $this->inheritanceService->getInheritanceData($element, $fieldDefinitions)
            );
        }

        $dataObject->setHasWorkflowAvailable($this->workflowDetailsService->hasElementWorkflows($element));
    }

    public function getNormalizedValue(
        mixed $value,
        Data $fieldDefinition
    ): mixed {
        if (!$fieldDefinition instanceof NormalizerInterface) {
            return null;
        }

        $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());
        if ($adapter instanceof DataNormalizerInterface) {
            return $adapter->normalize($value, $fieldDefinition);
        }

        return $fieldDefinition->normalize($value);
    }

    /**
     * @throws NotFoundException
     */
    private function getNormalizedObjectData(Concrete $dataObject, array $fieldDefinitions): array
    {
        $data = [];
        foreach ($fieldDefinitions as $key => $fieldDefinition) {
            $data[$key] = $this->getNormalizedValue(
                $this->getValidFieldValue($dataObject, $key),
                $fieldDefinition
            );
        }

        return $data;
    }

    private function getObjectClassData(ClassDefinition $class): ClassData
    {

        return new ClassData(
            $class->getAllowInherit(),
            $class->getAllowVariants(),
            $class->getShowVariants(),
            (bool)$class->getLinkGeneratorReference()
        );
    }
}
