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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinition as ClassDefinitionSchema;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionBrickData;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionBrickField;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * @internal
 */
final readonly class ClassDefinitionHydrator implements ClassDefinitionHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService
    ) {
    }

    public function hydrate(ClassDefinition $data): ClassDefinitionSchema
    {
        return new ClassDefinitionSchema(
            $data->getId(),
            $data->getName(),
            $data->getTitle(),
            $data->getDescription(),
            $data->getCreationDate(),
            $data->getModificationDate(),
            $data->getUserOwner(),
            $data->getParentClass(),
            $data->getImplementsInterfaces() ?? '',
            $data->getListingParentClass(),
            $data->getUseTraits(),
            $data->getListingUseTraits(),
            $data->getEncryption(),
            $data->getAllowInherit(),
            $data->getAllowVariants(),
            $data->getShowVariants(),
            $this->iconService->getIconForClassDefinition($data->getIcon()),
            $data->getShowAppLoggerTab(),
            $data->getLinkGeneratorReference() ?? '',
            $data->getPreviewGeneratorReference() ?? '',
            $data->getCompositeIndices(),
            $data->getShowFieldLookup(),
            $data->getPropertyVisibility(),
            $data->isEnableGridLocking(),
            $data->getBlockedVarsForExport(),
            $data->isWritable(),
            $data->getGroup(),
        );
    }

    public function hydrateBrickData(string $key, string $fieldName): ClassDefinitionBrickData
    {
        return new ClassDefinitionBrickData($key, $fieldName);
    }

    public function hydrateBrickField(string $fieldName): ClassDefinitionBrickField
    {
        return new ClassDefinitionBrickField($fieldName);
    }
}
