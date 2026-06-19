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

namespace Pimcore\Bundle\StudioBackendBundle\Configuration\Share\Service;

use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\ShareableConfigurationInterface;
use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\ShareOptionsInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ConfigurationShareServiceInterface
{
    /**
     * @template T of ShareableConfigurationInterface
     *
     * @param T $configuration
     *
     * @return T
     */
    public function setShareOptions(
        ShareableConfigurationInterface $configuration,
        ShareOptionsInterface $options,
    ): ShareableConfigurationInterface;

    public function isConfigurationSharedWithUser(
        ShareableConfigurationInterface $configuration,
        UserInterface $user,
    ): bool;

    /**
     * @return int[]
     */
    public function getUserShares(ShareableConfigurationInterface $configuration): array;

    /**
     * @return int[]
     */
    public function getRoleShares(ShareableConfigurationInterface $configuration): array;
}
