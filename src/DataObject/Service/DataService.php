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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\ClassData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final readonly class DataService implements DataServiceInterface
{
    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function getObjectData(Concrete $dataObject): array
    {
        $data = [];
        try {
            $fieldDefinitions = $dataObject->getClass()->getFieldDefinitions();
        } catch (Exception) {
            throw new NotFoundException(type: 'class', id: $dataObject->getClassId());
        }

        foreach ($fieldDefinitions as $key => $fieldDefinition) {
            try {
                $data[$key] = $this->getNormalizedValue($dataObject->get($key), $fieldDefinition);
            } catch (Exception) {
                throw new NotFoundException(type: 'field', id: $key);
            }
        }

        return $data;
    }

    /**
     * @throws NotFoundException
     */
    public function getObjectClassData(Concrete $dataObject): ClassData
    {
        try {
            $class = $dataObject->getClass();
        } catch (Exception) {
            throw new NotFoundException(type: 'class', id: $dataObject->getClassId());
        }

        return new ClassData(
            $class->getAllowInherit(),
            $class->getAllowVariants(),
            $class->getShowVariants(),
            (bool)$class->getLinkGeneratorReference()
        );
    }
    
    public function getNormalizedValue(
        mixed $value,
        Data $fieldDefinition
    ): mixed
    {
        if (!$fieldDefinition instanceof NormalizerInterface) {
            return null;
        }

        $adapter = $this->dataAdapterService->getDataAdapter($fieldDefinition->getFieldType());
        if ($adapter instanceof DataNormalizerInterface) {
            return $adapter->normalize($value, $fieldDefinition);
        }

        return $fieldDefinition->normalize($value);
    }
}
