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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration as ConfigurationSchema;

/**
 * @internal
 */
final readonly class ConfigurationHydrator implements ConfigurationHydratorInterface
{
    public function hydrate(GridConfiguration $data): ConfigurationSchema
    {
        return new ConfigurationSchema(
            $data->getId(),
            $data->getName(),
            $data->getDescription()
        );
    }
}
