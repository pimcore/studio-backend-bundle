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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ObjectBricksAdapter implements SetterDataInterface
{
    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
        private DefinitionResolverInterface $definitionResolver
    )
    {
    }

    /**
     * @throws Exception
     */
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?Objectbrick
    {
        if (!array_key_exists($key, $data) || !$fieldDefinition instanceof Objectbricks) {
            return null;
        }
        $brickData = $data[$key];
        $container = $this->getContainer($element, $key, $fieldDefinition->getName(), $contextData);

        foreach ($brickData as $collectionRaw) {
            $this->processBrickData($element, $container, $fieldDefinition, $collectionRaw);
        }

        return $container;
    }

    /**
     * @throws Exception
     */
    private function getContainer(
        Concrete $element,
        string $key,
        string $fieldName,
        ?FieldContextData $contextData
    ): Objectbrick
    {
        $container = $element->get($key, $contextData?->getLanguage());

        if ($container instanceof Objectbrick) {
            return $container;
        }

        $className = $element->getClass()->getName();
        $containerClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\' . ucfirst($fieldName);

        return new $containerClass($element, $fieldName);
    }

    private function getBrick(Concrete $element, Objectbrick $container, string $brickType): AbstractData
    {
        $brick = $container->get($brickType);
        if ($brick instanceof AbstractData) {
            return $brick;
        }

        $brickClass = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickType);
        return new $brickClass($element);
    }

    /**
     * @throws Exception
     */
    private function processBrickData(
        Concrete $element,
        Objectbrick $container,
        Data $fieldDefinition,
        array $collectionRaw
    ): void {
        $collectionDef = $this->definitionResolver->getByKey($collectionRaw['type']);
        if ($collectionDef === null) {
            return;
        }

        $brick = $this->getBrick($element, $container, $collectionRaw['type']);
        $brick->setFieldname($fieldDefinition->getName());
        if ($collectionRaw['data'] === 'deleted') {
            $brick->setDoDelete(true);
            return;
        }

        $brick->setValues($this->getCollectionData(
            $collectionDef,
            $collectionRaw['data'],
            $element,
            $brick,
        ));
        $container->set($collectionRaw['type'], $brick);
    }

    private function getCollectionData(
        Definition $collectionDef,
        array $rawData,
        Concrete $element,
        AbstractData $brick
    ): array
    {
        $collectionData = [];
        foreach ($collectionDef->getFieldDefinitions() as $fd) {
            $fieldName = $fd->getName();
            if (!array_key_exists($fieldName, $rawData)) {
                continue;
            }

            $adapter = $this->dataAdapterService->getDataAdapter($fd->getFieldType());
            $collectionData[$fd->getName()] = $adapter->getDataForSetter(
                $element,
                $fd,
                $fieldName,
                [$fieldName => $rawData[$fieldName]],
                new FieldContextData(objectbrick: $brick)
            );
        }

        return $collectionData;
    }
}
