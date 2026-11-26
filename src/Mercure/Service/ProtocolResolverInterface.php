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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\ProtocolVersion;

/**
 * @internal
 */
interface ProtocolResolverInterface
{
    /**
     * The protocol clients subscribe with: the configured one, or the one detected on the hub.
     * Never ProtocolVersion::AUTO.
     */
    public function getProtocolVersion(): ProtocolVersion;
}
