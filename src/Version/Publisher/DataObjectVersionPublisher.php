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
use Pimcore\Model\DataObject;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class DataObjectVersionPublisher implements DataObjectVersionPublisherInterface
{
    public function publish(
        DataObject $versionDataObject,
        UserInterface $user
    ): void
    {
        try {
            if (!$versionDataObject instanceof DataObject\Concrete) {
               return;
            }

            $versionDataObject->setPublished(true);
            $versionDataObject->setUserModification($user->getId());
            $versionDataObject->save();
        } catch (Exception $e) {
            throw new ElementPublishingFailedException(
                $versionDataObject->getId(),
                $e->getMessage()
            );
        }
    }

}