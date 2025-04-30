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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\ConfigurationParameterInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface UserRoleShareServiceInterface
{
    public function setShareOptions(
        GridConfiguration $configuration,
        ConfigurationParameterInterface $options
    ): GridConfiguration;

    public function isConfigurationSharedWithUser(GridConfiguration $gridConfiguration, UserInterface $user): bool;

    public function getUserShares(GridConfiguration $gridConfiguration): array;

    public function getRoleShares(GridConfiguration $gridConfiguration): array;
}
