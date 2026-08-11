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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'TelemetryOutboxBatch',
    title: 'Telemetry Outbox Batch',
    required: ['nonce', 'instanceIdentifier', 'v', 'ciphertext', 'relayEndpoint'],
    type: 'object'
)]
final class OutboxBatch implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(
            description: 'Lease nonce identifying the claimed batch, passed back on ack',
            type: 'string',
            example: '9b1c4f7a2e6d40318c5a7b9e0d2f4a61'
        )]
        private readonly string $nonce,
        #[Property(
            description: 'Cleartext instance identifier the relay uses to look up the product key',
            type: 'string',
            example: 'my-pimcore-instance'
        )]
        private readonly string $instanceIdentifier,
        #[Property(description: 'Outbox payload protocol version', type: 'integer', example: 1)]
        private readonly int $v,
        #[Property(
            description: 'Opaque product-key encrypted envelope to forward verbatim to the relay',
            type: 'string',
            example: 'AqiJ8s3TnQz1FQmSMHBZR2xlYXNlZC10ZWxlbWV0cnktZW52ZWxvcGU='
        )]
        private readonly string $ciphertext,
        #[Property(
            description: 'Relay endpoint the browser must POST the ciphertext to',
            type: 'string',
            example: 'https://relay.example.com/'
        )]
        private readonly string $relayEndpoint,
    ) {
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function getInstanceIdentifier(): string
    {
        return $this->instanceIdentifier;
    }

    public function getV(): int
    {
        return $this->v;
    }

    public function getCiphertext(): string
    {
        return $this->ciphertext;
    }

    public function getRelayEndpoint(): string
    {
        return $this->relayEndpoint;
    }
}
