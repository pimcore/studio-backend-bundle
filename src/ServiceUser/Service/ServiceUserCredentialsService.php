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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ServiceUserClientException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\ServiceUserClientServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserCredentials;
use Pimcore\Model\User\ServiceUser;

/**
 * @internal
 */
final readonly class ServiceUserCredentialsService implements ServiceUserCredentialsServiceInterface
{
    public function __construct(
        private ServiceUserClientServiceInterface $clientService,
        private ScopeRegistryInterface $scopeRegistry,
        private bool $clientCredentialsEnabled,
    ) {
    }

    public function register(ServiceUser $serviceUser, ?array $scopes): ServiceUserCredentials
    {
        try {
            $credentials = $this->clientService->register(
                $serviceUser,
                (string) $serviceUser->getName(),
                $scopes ?? $this->scopeRegistry->all(),
            );
        } catch (ServiceUserClientException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception);
        }

        return new ServiceUserCredentials(
            (int) $serviceUser->getId(),
            $credentials->clientId,
            $this->secretForResponse($credentials->clientSecret),
        );
    }

    public function rotate(ServiceUser $serviceUser): ServiceUserCredentials
    {
        $id = (int) $serviceUser->getId();

        try {
            $credentials = $this->clientService->rotateSecret($serviceUser);
        } catch (ServiceUserClientException) {
            throw new NotFoundException('Client of service user', $id);
        }

        // Rotation still invalidates the old secret while the grant is off; only the new one is withheld.
        return new ServiceUserCredentials(
            $id,
            $credentials->clientId,
            $this->secretForResponse($credentials->clientSecret),
        );
    }

    /**
     * The secret is returned only while the client_credentials grant is enabled; otherwise it could not be used.
     */
    private function secretForResponse(string $clientSecret): ?string
    {
        return $this->clientCredentialsEnabled ? $clientSecret : null;
    }
}
