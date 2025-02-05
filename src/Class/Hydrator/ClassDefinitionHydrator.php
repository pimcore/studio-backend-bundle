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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinition as ClassDefinitionSchema;
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
            $data->getImplementsInterfaces(),
            $data->getListingParentClass(),
            $data->getUseTraits(),
            $data->getListingUseTraits(),
            $data->getEncryption(),
            $data->getAllowInherit(),
            $data->getAllowVariants(),
            $data->getShowVariants(),
            $this->iconService->getIconForClassDefinition($data->getIcon()),
            $data->getShowAppLoggerTab(),
            $data->getLinkGeneratorReference(),
            $data->getPreviewGeneratorReference(),
            $data->getCompositeIndices(),
            $data->getShowFieldLookup(),
            $data->getPropertyVisibility(),
            $data->isEnableGridLocking(),
            $data->getBlockedVarsForExport(),
            $data->isWritable(),
            $data->getGroup(),
        );
    }
}
