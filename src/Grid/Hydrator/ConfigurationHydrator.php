<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
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