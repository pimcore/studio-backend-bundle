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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ServiceUserClientException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ServiceUserClientStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\ServiceUserClientServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Event\ServiceUserEvent;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Hydrator\ServiceUserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\CreateFromUserParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\CreateParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\UpdateParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Repository\ServiceUserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUser as ServiceUserSchema;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserCredentials;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\WorkspaceCloneServiceInterface;
use Pimcore\Model\User;
use Pimcore\Model\User\ServiceUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;
use function implode;
use function sprintf;

/**
 * @internal
 */
final readonly class ServiceUserService implements ServiceUserServiceInterface
{
    public function __construct(
        private ServiceUserRepositoryInterface $repository,
        private UserRepositoryInterface $userRepository,
        private UpdateServiceInterface $updateService,
        private WorkspaceCloneServiceInterface $workspaceCloneService,
        private ServiceUserClientServiceInterface $clientService,
        private ServiceUserClientStoreInterface $clientStore,
        private ServiceUserHydratorInterface $hydrator,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
        private ScopeRegistryInterface $scopeRegistry,
    ) {
    }

    public function listServiceUsers(): Collection
    {
        $items = [];
        foreach ($this->repository->listServiceUsers() as $serviceUser) {
            $items[] = $this->buildSchema($serviceUser);
        }

        return new Collection(count($items), $items);
    }

    public function getServiceUser(int $id): ServiceUserSchema
    {
        return $this->buildSchema($this->repository->getServiceUser($id));
    }

    public function createServiceUser(CreateParameter $parameter): ServiceUserCredentials
    {
        $serviceUser = $this->newServiceUser($parameter->getName(), $parameter->getEmail());
        $this->repository->saveServiceUser($serviceUser);

        try {
            // Workspaces carry the user id, so they are applied once the user is saved.
            $this->applyAuthorization(
                $serviceUser,
                $parameter->getPermissions(),
                $parameter->getRoles(),
                $parameter->getClasses(),
                $parameter->getDocTypes(),
            );
            $this->updateService->updateAssetWorkspaces($parameter->getAssetWorkspaces(), $serviceUser);
            $this->updateService->updateDataObjectWorkspaces($parameter->getDataObjectWorkspaces(), $serviceUser);
            $this->updateService->updateDocumentWorkspaces($parameter->getDocumentWorkspaces(), $serviceUser);
            $this->repository->saveServiceUser($serviceUser);

            return $this->registerClient($serviceUser, $parameter->getScopes());
        } catch (Exception $exception) {
            $this->repository->deleteServiceUser($serviceUser);

            throw $exception;
        }
    }

    public function createServiceUserFromUser(int $userId, CreateFromUserParameter $parameter): ServiceUserCredentials
    {
        $source = $this->userRepository->getUserById($userId);
        if (!$source instanceof User || $source instanceof ServiceUser) {
            throw new NotFoundException('User', $userId);
        }

        if ($source->isAdmin() && !$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Only admins can create a service user from an admin.');
        }

        $serviceUser = $this->newServiceUser($parameter->getName(), $parameter->getEmail());
        $serviceUser->setPermissions($source->getPermissions());
        $serviceUser->setRoles($source->getRoles());
        $serviceUser->setClasses($source->getClasses());
        $serviceUser->setDocTypes($source->getDocTypes());
        $serviceUser->setWebsiteTranslationLanguagesView($source->getWebsiteTranslationLanguagesView());
        $serviceUser->setWebsiteTranslationLanguagesEdit($source->getWebsiteTranslationLanguagesEdit());
        $this->repository->saveServiceUser($serviceUser);

        try {
            $this->cloneWorkspaces($source, $serviceUser);
            $this->repository->saveServiceUser($serviceUser);

            return $this->registerClient($serviceUser, $parameter->getScopes());
        } catch (Exception $exception) {
            $this->repository->deleteServiceUser($serviceUser);

            throw $exception;
        }
    }

    public function updateServiceUser(int $id, UpdateParameter $parameter): ServiceUserSchema
    {
        $refused = $parameter->getRefusedFields();
        if ($refused !== []) {
            throw new InvalidArgumentException(
                sprintf('A service user has no such field: %s.', implode(', ', $refused))
            );
        }

        $serviceUser = $this->repository->getServiceUser($id);

        $serviceUser->setEmail($parameter->getEmail());
        $this->applyAuthorization(
            $serviceUser,
            $parameter->getPermissions(),
            $parameter->getRoles(),
            $parameter->getClasses(),
            $parameter->getDocTypes(),
        );
        $this->updateService->updateAssetWorkspaces($parameter->getAssetWorkspaces(), $serviceUser);
        $this->updateService->updateDataObjectWorkspaces($parameter->getDataObjectWorkspaces(), $serviceUser);
        $this->updateService->updateDocumentWorkspaces($parameter->getDocumentWorkspaces(), $serviceUser);
        $this->repository->saveServiceUser($serviceUser);

        return $this->buildSchema($serviceUser);
    }

    public function deactivateServiceUser(int $id): ServiceUserSchema
    {
        $serviceUser = $this->repository->getServiceUser($id);
        $serviceUser->setActive(false);
        $this->repository->saveServiceUser($serviceUser);

        return $this->buildSchema($serviceUser);
    }

    public function deleteServiceUser(int $id): void
    {
        $this->repository->deleteServiceUser($this->repository->getServiceUser($id));
    }

    public function rotateSecret(int $id): ServiceUserCredentials
    {
        $serviceUser = $this->repository->getServiceUser($id);

        try {
            $credentials = $this->clientService->rotateSecret($serviceUser);
        } catch (ServiceUserClientException) {
            throw new NotFoundException('Client of service user', $id);
        }

        return new ServiceUserCredentials($id, $credentials->clientId, $credentials->clientSecret);
    }

    private function newServiceUser(string $name, ?string $email): ServiceUser
    {
        $serviceUser = new ServiceUser();
        $serviceUser->setParentId(0);
        $serviceUser->setName($name);
        $serviceUser->setEmail($email);
        $serviceUser->setActive(true);

        return $serviceUser;
    }

    /**
     * @param string[] $permissions
     * @param int[] $roles
     * @param string[] $classes
     * @param string[] $docTypes
     *
     * @throws NotFoundException
     */
    private function applyAuthorization(
        ServiceUser $serviceUser,
        array $permissions,
        array $roles,
        array $classes,
        array $docTypes,
    ): void {
        $this->updateService->updatePermissions($permissions, $serviceUser);
        $this->updateService->updateRoles($roles, $serviceUser);
        $this->updateService->updateClasses($classes, $serviceUser);
        $serviceUser->setDocTypes($docTypes);
    }

    /**
     * @throws Exception
     */
    private function cloneWorkspaces(User $source, ServiceUser $serviceUser): void
    {
        $serviceUserId = (int) $serviceUser->getId();

        $assetWorkspaces = [];
        foreach ($source->getWorkspacesAsset() as $workspace) {
            $clone = $this->workspaceCloneService->cloneAssetWorkspace($workspace);
            $clone->setUserId($serviceUserId);
            $assetWorkspaces[] = $clone;
        }
        $serviceUser->setWorkspacesAsset($assetWorkspaces);

        $documentWorkspaces = [];
        foreach ($source->getWorkspacesDocument() as $workspace) {
            $clone = $this->workspaceCloneService->cloneDocumentWorkspace($workspace);
            $clone->setUserId($serviceUserId);
            $documentWorkspaces[] = $clone;
        }
        $serviceUser->setWorkspacesDocument($documentWorkspaces);

        $objectWorkspaces = [];
        foreach ($source->getWorkspacesObject() as $workspace) {
            $clone = $this->workspaceCloneService->cloneDataObjectWorkspace($workspace);
            $clone->setUserId($serviceUserId);
            $objectWorkspaces[] = $clone;
        }
        $serviceUser->setWorkspacesObject($objectWorkspaces);
    }

    /**
     * @param list<string>|null $scopes
     *
     * @throws InvalidArgumentException
     */
    private function registerClient(ServiceUser $serviceUser, ?array $scopes): ServiceUserCredentials
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
            $credentials->clientSecret,
        );
    }

    private function buildSchema(ServiceUser $serviceUser): ServiceUserSchema
    {
        $client = $this->clientStore->findByServiceUserId((int) $serviceUser->getId());
        $schema = $this->hydrator->hydrate($serviceUser, $client?->identifier);

        $this->eventDispatcher->dispatch(new ServiceUserEvent($schema), ServiceUserEvent::EVENT_NAME);

        return $schema;
    }
}
