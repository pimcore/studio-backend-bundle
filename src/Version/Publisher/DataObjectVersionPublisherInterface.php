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

use Pimcore\Model\DataObject;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;

/**
 * @internal
 */
interface DataObjectVersionPublisherInterface
{
    /**
     * Publishes the state restored from $version into $versionDataObject and writes it into $version itself
     */
    public function publish(
        DataObject $versionDataObject,
        UserInterface $user,
        Version $version
    ): void;
}
