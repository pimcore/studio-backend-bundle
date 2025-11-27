<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\Legacy;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * Copied from old admin-ui-classic-bundle
 * https://github.com/pimcore/admin-ui-classic-bundle/blob/9258d42920dbb475badc1adea59a7552ab089ac4/src/GDPR/DataProvider/Exporter.php#L32
 * Use with caution, this is a copy from the admin-ui-classic-bundle
 *
 * @internal
 */
final readonly class ObjectExporter implements ObjectExporterInterface
{
    public function doExportObject(Concrete $object, array &$result = []): void
    {
        $fDefs = $object->getClass()->getFieldDefinitions();

        foreach ($fDefs as $fd) {
            $getter = 'get' . ucfirst($fd->getName());
            $value = $object->$getter();

            if ($fd instanceof Data\Fieldcollections && $value instanceof Fieldcollection) {
                self::doExportFieldcollection($result, $value);
            } elseif ($fd instanceof Data\Objectbricks && $value instanceof Objectbrick) {
                self::doExportBrick($result, $value);
            } else {
                if ($fd instanceof NormalizerInterface
                    && $fd instanceof DataObject\ClassDefinition\Data) {
                    $marshalledValue = $fd->normalize($value);
                    $result[$fd->getName()] = $marshalledValue;
                }
            }
        }
    }

    private function doExportBrick(array &$result, Objectbrick $container): void
    {
        $allowedBrickTypes = $container->getAllowedBrickTypes();
        $resultContainer = [];
        foreach ($allowedBrickTypes as $brickType) {
            $brickDef = Objectbrick\Definition::getByKey($brickType);
            $brickGetter = 'get' . ucfirst($brickType);
            $brickValue = $container->$brickGetter();

            if ($brickValue instanceof Objectbrick\Data\AbstractData) {
                $resultContainer[$brickType] = [];
                $fDefs = $brickDef->getFieldDefinitions();
                foreach ($fDefs as $fd) {
                    $getter = 'get' . ucfirst($fd->getName());
                    $value = $brickValue->$getter();
                    if ($fd instanceof NormalizerInterface
                        && $fd instanceof DataObject\ClassDefinition\Data) {
                        $marshalledValue = $fd->normalize($value);
                        $resultContainer[$brickType][$fd->getName()] = $marshalledValue;
                    }
                }
            }
        }
        $result[$container->getFieldname()] = $resultContainer;
    }

    private function doExportFieldcollection(array &$result, Fieldcollection $container): void
    {
        $resultContainer = [];

        $items = $container->getItems();
        foreach ($items as $item) {
            $type = $item->getType();

            $itemValues = [];

            $itemContainerDefinition = Fieldcollection\Definition::getByKey($type);
            $fDefs = $itemContainerDefinition->getFieldDefinitions();

            foreach ($fDefs as $fd) {
                $getter = 'get' . ucfirst($fd->getName());
                $value = $item->$getter();

                if ($fd instanceof NormalizerInterface
                    && $fd instanceof DataObject\ClassDefinition\Data) {
                    $marshalledValue = $fd->normalize($value);
                    $itemValues[$fd->getName()] = $marshalledValue;
                }
            }

            $resultContainer[] = [
                'type' => $type,
                'value' => $itemValues,
            ];
        }
        $result[$container->getFieldname()] = $resultContainer;
    }
}
