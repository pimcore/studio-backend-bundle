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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
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
        private ServiceResolverInterface $serviceResolver
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getLayoutDefinition(
        KeyGroupRelation $keyGroupRelation,
        Concrete $object,
        string $fieldName
    ): EncryptedField|Data {
        $definition = json_decode($keyGroupRelation->getDefinition(), true);
        $definition = $this->serviceResolver->getFieldDefinitionFromJson(
            $definition,
            $keyGroupRelation->getType()
        );

        if (method_exists($definition, '__wakeup')) {
            $definition->__wakeup();
        }

        if ($definition instanceof LayoutDefinitionEnrichmentInterface) {
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
}
