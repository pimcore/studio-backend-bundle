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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Service;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ClientCredentials;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ServiceUserClientException;
use Pimcore\Model\User\ServiceUser;

/**
 * Manages the one confidential client_credentials client bound to a service user.
 *
 * @internal
 */
interface ServiceUserClientServiceInterface
{
    /**
     * Registers the service user's client. The returned secret is shown once; only its
     * hash is stored.
     *
     * @param list<string> $scopes the scopes tokens for this client may carry
     *
     * @throws ServiceUserClientException when the service user is unsaved, already has a
     *                                    client, or a scope is unknown
     */
    public function register(ServiceUser $serviceUser, string $name, array $scopes): ClientCredentials;

    /**
     * Replaces the client's secret. The previous secret stops validating at once; tokens
     * issued before stay valid until they expire.
     *
     * @throws ServiceUserClientException when the service user has no client
     */
    public function rotateSecret(ServiceUser $serviceUser): ClientCredentials;
}
