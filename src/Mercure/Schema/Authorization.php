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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'MercureAuthorization',
    required: [
        'cookieLifetime',
    ],
    type: 'object'
)]
final readonly class Authorization
{
    public function __construct(
        #[Property(
            description: 'Lifetime of the authorization cookie in seconds. A client has to request a new ' .
                'cookie before it elapses: the hub authorises a subscription once, at connect time, so an ' .
                'expired cookie leaves every reconnect anonymous and silently drops all private updates.',
            type: 'integer',
            example: 3600
        )]
        private int $cookieLifetime
    ) {
    }

    public function getCookieLifetime(): int
    {
        return $this->cookieLifetime;
    }
}
