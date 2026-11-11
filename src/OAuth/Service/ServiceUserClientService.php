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

use InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ClientCredentials;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ServiceUserClientException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ServiceUserClientStoreInterface;
use Pimcore\Model\User;
use function bin2hex;
use function hash;
use function random_bytes;
use function sprintf;

/**
 * @internal
 */
final readonly class ServiceUserClientService implements ServiceUserClientServiceInterface
{
    private const string CLIENT_ID_PREFIX = 'svc_';

    private const string GRANT_TYPE = 'client_credentials';

    public function __construct(
        private ServiceUserClientStoreInterface $store,
        private ScopeRegistryInterface $scopeRegistry,
    ) {
    }

    public function register(User $serviceUser, string $name, array $scopes): ClientCredentials
    {
        if (!$serviceUser->isServiceUser()) {
            throw new InvalidArgumentException('Expected a service user, got a person: ' . (string) $serviceUser->getName());
        }

        $serviceUserId = $this->serviceUserId($serviceUser);

        if ($this->store->findByServiceUserId($serviceUserId) !== null) {
            throw new ServiceUserClientException('This service user already has a client.');
        }

        foreach ($scopes as $scope) {
            if (!$this->scopeRegistry->has($scope)) {
                throw new ServiceUserClientException(sprintf('Unknown scope "%s".', $scope));
            }
        }

        $clientId = self::CLIENT_ID_PREFIX . bin2hex(random_bytes(16));
        $secret = $this->newSecret();

        $this->store->save(new DynamicClient(
            $clientId,
            $name,
            [],
            [self::GRANT_TYPE],
            $scopes,
            true,
            hash('sha256', $secret),
            serviceUserId: $serviceUserId,
        ));

        return new ClientCredentials($clientId, $secret);
    }

    public function rotateSecret(User $serviceUser): ClientCredentials
    {
        if (!$serviceUser->isServiceUser()) {
            throw new InvalidArgumentException('Expected a service user, got a person: ' . (string) $serviceUser->getName());
        }

        $client = $this->store->findByServiceUserId($this->serviceUserId($serviceUser));
        if ($client === null) {
            throw new ServiceUserClientException('This service user has no client.');
        }

        $secret = $this->newSecret();
        $this->store->replaceSecretHash($client->identifier, hash('sha256', $secret));

        return new ClientCredentials($client->identifier, $secret);
    }

    private function serviceUserId(User $serviceUser): int
    {
        $id = $serviceUser->getId();
        if ($id === null) {
            throw new ServiceUserClientException('The service user must be saved first.');
        }

        return $id;
    }

    private function newSecret(): string
    {
        // 256 bits from the CSPRNG: a plain SHA-256 of it is a sufficient verifier, as
        // for dynamically registered clients.
        return bin2hex(random_bytes(32));
    }
}
