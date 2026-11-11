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
use Pimcore\Model\User\ServiceUser;
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
        $summaries = $this->mcpTokenRepository->summarizeByUserIds(
            array_map(static fn (ServiceUser $serviceUser): int => (int) $serviceUser->getId(), $serviceUsers),
            time()
        );

        $items = [];
        foreach ($serviceUsers as $serviceUser) {
            $items[] = $this->build($serviceUser, $summaries);
        }

        return $items;
    }

    public function build(ServiceUser $serviceUser, ?array $summaries = null): ServiceUserSchema
    {
        $id = (int) $serviceUser->getId();
        $summaries ??= $this->mcpTokenRepository->summarizeByUserIds([$id], time());
        $summary = $summaries[$id] ?? null;

        $client = $this->clientStore->findByServiceUserId($id);
        $schema = $this->hydrator->hydrate(
            $serviceUser,
            $client?->identifier,
            new ServiceUserMcpTokens($summary['active'] ?? 0, $summary['latestExpiresAt'] ?? null)
        );

        $this->eventDispatcher->dispatch(new ServiceUserEvent($schema), ServiceUserEvent::EVENT_NAME);

        return $schema;
    }
}
