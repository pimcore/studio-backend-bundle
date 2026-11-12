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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DetailDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * Turns a raw field value into the shape the Studio API exposes in `objectData`.
 *
 * @internal
 */
trait DetailValueTrait
{
    private function normalizeFieldValue(
        DataAdapterServiceInterface $dataAdapterService,
        mixed $value,
        Data $fieldDefinition
    ): mixed {
        $adapter = $dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());
        if ($adapter instanceof DataNormalizerInterface) {
            return $adapter->normalize($value, $fieldDefinition);
        }

        if (!$fieldDefinition instanceof NormalizerInterface) {
            return null;
        }

        return $fieldDefinition->normalize($value);
    }

    private function resolveDetailValue(
        DataAdapterServiceInterface $dataAdapterService,
        Concrete $object,
        mixed $value,
        Data $fieldDefinition,
        ?FieldContextData $contextData = null,
    ): mixed {
        $adapter = $dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());
        if ($adapter instanceof DetailDataInterface) {
            return $adapter->getDetailData($object, $value, $fieldDefinition, $contextData);
        }

        return $this->normalizeFieldValue($dataAdapterService, $value, $fieldDefinition);
    }
}
