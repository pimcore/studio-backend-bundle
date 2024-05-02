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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Version\Schema\User;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\Version;
use Pimcore\Model\Version as PimcoreVersion;

/**
 * @internal
 */
final class VersionHydrator implements VersionHydratorInterface
{
    public function hydrate(
        PimcoreVersion $version,
        array $scheduledTasks,
    ): Version {
        $user = new User();
        if ($version->getUser()) {
            $user = new User(
                $version->getUser()->getId(),
                $version->getUser()->getName()
            );
        }
        $versionId = $version->getId();

        return new Version(
            id: $versionId,
            cid: $version->getCid(),
            ctype: $version->getCtype(),
            note: $version->getNote(),
            date: $version->getDate(),
            public: $version->isPublic(),
            versionCount: $version->getVersionCount(),
            autosave: $version->isAutoSave(),
            user: $user,
            scheduled: $scheduledTasks[$versionId] ?? null
        );
    }
}
