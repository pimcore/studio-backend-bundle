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

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionList;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionList as ClassDefinitionSchema;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * @internal
 */
final readonly class ClassDefinitionListHydrator implements ClassDefinitionListHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService
    ) {
    }

    public function hydrate(ClassDefinition $data): ClassDefinitionSchema
    {
        return new ClassDefinitionList(
            $data->getId(),
            $data->getName(),
            $data->getTitle(),
            $this->iconService->getIconForClassDefinition($data->getIcon()),
            $data->getGroup()
        );
    }
}
