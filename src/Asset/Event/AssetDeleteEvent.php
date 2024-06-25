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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Event;

use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\ElementDeleteParamsTrait;
use Pimcore\Event\Model\AssetEvent;

final class AssetDeleteEvent extends AssetEvent
{
    public const EVENT_NAME = 'asset.delete';

    use ElementDeleteParamsTrait;
}
