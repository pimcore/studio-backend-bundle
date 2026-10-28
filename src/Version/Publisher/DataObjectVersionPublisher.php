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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementPublishingFailedException;
use Pimcore\Model\DataObject;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;

/**
 * @internal
 */
final class DataObjectVersionPublisher implements DataObjectVersionPublisherInterface
{
    public function publish(
        DataObject $versionDataObject,
        UserInterface $user,
        Version $version
    ): void {
        try {
            if (!$versionDataObject instanceof DataObject\Concrete) {
                return;
            }

            $versionDataObject->setPublished(true);
            $versionDataObject->setUserModification($user->getId());
            // the published state is written into the version itself, so the history shows which version went live;
            // an auto-save version is rewritten by the editor of its user at any time, it is published as a new version
            $versionDataObject->save($version->isAutoSave() ? [] : ['existingVersion' => $version]);
        } catch (Exception $e) {
            throw new ElementPublishingFailedException(
                $versionDataObject->getId(),
                $e->getMessage()
            );
        }
    }
}
