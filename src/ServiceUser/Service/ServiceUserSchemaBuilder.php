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

use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ServiceUserClientStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Repository\McpAccessTokenRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Event\ServiceUserEvent;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Hydrator\ServiceUserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUser as ServiceUserSchema;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserMcpTokens;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function time;

/**
 * @internal
 */
final readonly class ServiceUserSchemaBuilder implements ServiceUserSchemaBuilderInterface
{
    public function __construct(
        private ServiceUserClientStoreInterface $clientStore,
        private ServiceUserHydratorInterface $hydrator,
        private EventDispatcherInterface $eventDispatcher,
        private McpAccessTokenRepositoryInterface $mcpTokenRepository,
    ) {
    }

    public function buildAll(array $serviceUsers): array
    {
        // One query each for the token summaries and the client ids of the whole collection.
        $ids = array_map(static fn (User $serviceUser): int => (int) $serviceUser->getId(), $serviceUsers);
        $summaries = $this->mcpTokenRepository->summarizeByUserIds($ids, time());
        $clientIds = $this->clientStore->findClientIdsByServiceUserIds($ids);

        $items = [];
        foreach ($serviceUsers as $serviceUser) {
            $id = (int) $serviceUser->getId();
            $items[] = $this->buildSchema($serviceUser, $summaries[$id] ?? null, $clientIds[$id] ?? null);
        }

        return $items;
    }

    public function build(User $serviceUser, ?array $summaries = null): ServiceUserSchema
    {
        $id = (int) $serviceUser->getId();
        $summaries ??= $this->mcpTokenRepository->summarizeByUserIds([$id], time());

        return $this->buildSchema(
            $serviceUser,
            $summaries[$id] ?? null,
            $this->clientStore->findByServiceUserId($id)?->identifier
        );
    }

    /**
     * @param array{active: int, latestExpiresAt: int}|null $summary
     */
    private function buildSchema(User $serviceUser, ?array $summary, ?string $clientId): ServiceUserSchema
    {
        $schema = $this->hydrator->hydrate(
            $serviceUser,
            $clientId,
            new ServiceUserMcpTokens($summary['active'] ?? 0, $summary['latestExpiresAt'] ?? null)
        );

        $this->eventDispatcher->dispatch(new ServiceUserEvent($schema), ServiceUserEvent::EVENT_NAME);

        return $schema;
    }
}
