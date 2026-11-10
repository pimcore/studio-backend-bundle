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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use SensitiveParameter;

/**
 * The client id and plain client secret of a service user's OAuth client. Returned once, when
 * the service user is created or its secret rotated; only the secret's hash is stored. No
 * pre-response event is dispatched for it, so the secret never reaches a listener.
 *
 * @internal
 */
#[Schema(
    schema: 'ServiceUserCredentials',
    title: 'Service User Credentials',
    description: 'Client credentials of a service user. The secret is shown only in this response.',
    required: ['serviceUserId', 'clientId', 'clientSecret'],
    type: 'object'
)]
final readonly class ServiceUserCredentials
{
    public function __construct(
        #[Property(description: 'ID of the service user', type: 'integer', example: 42)]
        private int $serviceUserId,
        #[Property(description: 'OAuth client id', type: 'string', example: 'svc_4f1c2b')]
        private string $clientId,
        #[Property(
            description: 'OAuth client secret, shown once; null while the client_credentials grant is disabled',
            type: 'string',
            example: '<secret>',
            nullable: true
        )]
        #[SensitiveParameter]
        private ?string $clientSecret,
    ) {
    }

    public function getServiceUserId(): int
    {
        return $this->serviceUserId;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }
}
