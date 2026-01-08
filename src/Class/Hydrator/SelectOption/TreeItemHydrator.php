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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\SelectOption;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionTree;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Model\DataObject\SelectOptions\Config;

/**
 * @internal
 */
final readonly class TreeItemHydrator implements TreeItemHydratorInterface
{
    public function hydrate(Config $config): SelectOptionTree
    {
        return new SelectOptionTree(
            $config->getId(),
            $config->getId(),
            new ElementIcon(ElementIconTypes::NAME->value, 'select'),
            $config->getGroup(),
            $config->getAdminOnly()
        );
    }
}
