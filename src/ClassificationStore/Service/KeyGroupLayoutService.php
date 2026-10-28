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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;
use Pimcore\Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final readonly class KeyGroupLayoutService implements KeyGroupLayoutServiceInterface
{
    public function __construct(
        private ServiceResolverInterface $serviceResolver,
        private KeyRelationResolverInterface $keyRelationResolver,
        private ConcreteObjectResolver $concreteObjectResolver,
        private KeyConfigResolverInterface $keyConfigResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutDefinition(
        KeyGroupRelation $keyGroupRelation,
        string $fieldName,
        ?Concrete $object = null,
    ): EncryptedField|Data {
        $definition = json_decode($keyGroupRelation->getDefinition(), true);
        $definition = $this->serviceResolver->getFieldDefinitionFromJson(
            $definition,
            $keyGroupRelation->getType()
        );

        if (method_exists($definition, '__wakeup')) {
            $definition->__wakeup();
        }

        if ($definition instanceof LayoutDefinitionEnrichmentInterface && $object) {
            $context['object'] = $object;
            $context['class'] = $object->getClass();
            $context['ownerType'] = 'classificationstore';
            $context['ownerName'] = $fieldName;
            $context['keyId'] = $keyGroupRelation->getKeyId();
            $context['groupId'] = $keyGroupRelation->getGroupId();
            $context['keyDefinition'] = $definition;

            $definition = $definition->enrichLayoutDefinition($object, $context);
        }

        return $definition;
    }

    /**
     * @throws Exception
     */
    public function getKeyLayout(
        LayoutParameter $layoutParameter,
        int $keyId,
        int $groupId
    ): KeyLayout {

        $object = null;
        if ($layoutParameter->getObjectId()) {
            $object = $this->concreteObjectResolver->getById($layoutParameter->getObjectId());

            if (!$object) {
                throw new NotFoundException('Object', $layoutParameter->getObjectId());
            }
        }

        $key = $this->keyRelationResolver->getByGroupAndKey($groupId, $keyId);
        $definition = $this->getLayoutDefinition($key, $layoutParameter->getFieldName(), $object);

        return new KeyLayout(
            id: $key->getKeyId(),
            name: $key->getName(),
            description: $key->getDescription(),
            definition: $definition,
            title: $this->getKeyTitle($key->getKeyId())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyTitle(int $keyId): ?string
    {
        return $this->keyConfigResolver->getById($keyId)?->getTitle();
    }
}
