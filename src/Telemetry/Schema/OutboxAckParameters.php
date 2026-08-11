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

namespace Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'TelemetryOutboxAckParameters',
    title: 'Telemetry Outbox Ack Parameters',
    required: ['nonce'],
    type: 'object'
)]
final readonly class OutboxAckParameters
{
    public function __construct(
        #[Property(
            description: 'Lease nonce of the batch the relay has accepted',
            type: 'string',
            example: '9b1c4f7a2e6d40318c5a7b9e0d2f4a61'
        )]
        private string $nonce,
    ) {
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }
}
