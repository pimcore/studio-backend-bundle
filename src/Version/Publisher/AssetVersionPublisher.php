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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Publisher;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementPublishingFailedException;
use Pimcore\Model\Asset;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class AssetVersionPublisher implements AssetVersionPublisherInterface
{
    public function publish(
        Asset $versionAsset,
        UserInterface $user
    ): void {
        try {
            $versionAsset->setUserModification($user->getId());
            $versionAsset->save();
        } catch (Exception $e) {
            throw new ElementPublishingFailedException(
                $versionAsset->getId(),
                $e->getMessage()
            );
        }
    }
}
