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


namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\Grid\ConfigurationParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;

/**
 * @internal
 */
interface UserRoleShareServiceInterface
{
    public function setShareOptions(
        GridConfiguration $configuration,
        ConfigurationParameterInterface $options
    ): GridConfiguration;
}