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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Entity\Notification\NotificationSubscription;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use function sprintf;

/**
 * @internal
 */
final readonly class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getByUser(int $userId): array
    {
        try {
            $rows = $this->entityManager->getRepository(NotificationSubscription::class)
                ->findBy(['userId' => $userId]);
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf('Failed to load notification subscriptions: %s', $e->getMessage()),
                $e
            );
        }

        $byType = [];
        foreach ($rows as $row) {
            $byType[$row->getTypeId()] = $row;
        }

        return $byType;
    }

    public function getByUserAndType(int $userId, string $typeId): ?NotificationSubscription
    {
        try {
            return $this->entityManager->getRepository(NotificationSubscription::class)
                ->find(['userId' => $userId, 'typeId' => $typeId]);
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf('Failed to load notification subscription: %s', $e->getMessage()),
                $e
            );
        }
    }

    public function save(int $userId, array $preferences): void
    {
        try {
            $existing = $this->getByUser($userId);

            foreach ($preferences as $typeId => $preference) {
                $row = $existing[$typeId] ?? null;

                if ($row === null) {
                    $this->entityManager->persist(
                        new NotificationSubscription(
                            $userId,
                            $typeId,
                            $preference['subscribed'],
                            $preference['channels']
                        )
                    );

                    continue;
                }

                $row->setSubscribed($preference['subscribed']);
                $row->setChannels($preference['channels']);
            }

            $this->entityManager->flush();
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf('Failed to save notification subscriptions: %s', $e->getMessage()),
                $e
            );
        }
    }
}
