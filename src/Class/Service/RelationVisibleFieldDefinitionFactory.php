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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleFieldsConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use function sprintf;

/**
 * @internal
 */
final readonly class RelationVisibleFieldDefinitionFactory implements RelationVisibleFieldDefinitionFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(RelationVisibleFieldsConfiguration $configuration): ManyToManyRelation
    {
        $fieldDefinition = match ($configuration->getFieldtype()) {
            RelationVisibleFieldsConfiguration::TYPE_MANY_TO_MANY_RELATION => new ManyToManyRelation(),
            RelationVisibleFieldsConfiguration::TYPE_ADVANCED_MANY_TO_MANY_RELATION => new AdvancedManyToManyRelation(),
            default => throw new InvalidArgumentException(
                sprintf('Field type %s does not support visible fields', $configuration->getFieldtype())
            ),
        };

        $fieldDefinition->setObjectsAllowed($configuration->isObjectsAllowed());
        $fieldDefinition->setClasses($this->toAllowedTypes($configuration->getClasses(), 'classes'));
        $fieldDefinition->setAssetsAllowed($configuration->isAssetsAllowed());
        $fieldDefinition->setAssetTypes($this->toAllowedTypes($configuration->getAssetTypes(), 'assetTypes'));
        $fieldDefinition->setDocumentsAllowed($configuration->isDocumentsAllowed());
        $fieldDefinition->setDocumentTypes(
            $this->toAllowedTypes($configuration->getDocumentTypes(), 'documentTypes')
        );

        return $fieldDefinition;
    }

    /**
     * @param string[] $names
     *
     * @return array<int, array<string, string>>
     */
    private function toAllowedTypes(array $names, string $key): array
    {
        return array_map(static fn (string $name): array => [$key => $name], $names);
    }
}
