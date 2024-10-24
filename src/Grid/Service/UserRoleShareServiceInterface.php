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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\Grid\ConfigurationParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
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
