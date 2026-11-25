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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant;

/**
 * Mercure protocol a hub speaks, as named by symfony/mercure.
 */
enum ProtocolVersion: string
{
    /** Detect the protocol of the hub configured as hub_url_server */
    case AUTO = 'auto';

    /** "topic" and "lastEventID" subscription parameters, spoken by 0.x hubs and 1.x hubs in compatibility mode */
    case LEGACY = '0.x';

    /** "match" and "last_event_id" subscription parameters */
    case V1 = '1.0';
}
