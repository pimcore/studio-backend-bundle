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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Publisher;

use Pimcore\Model\Asset;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface AssetVersionPublisherInterface
{
    public function publish(
        Asset $versionAsset,
        UserInterface $user
    ): void;
}
