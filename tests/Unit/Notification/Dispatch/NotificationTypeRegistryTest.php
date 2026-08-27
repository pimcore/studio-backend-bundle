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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidNotificationTypeException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistry;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\GeneralNotificationType;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationType;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestTypes;
use function array_map;
use function str_repeat;

final class NotificationTypeRegistryTest extends Unit
{
    public function testTypesAreOrderedBySortOrderThenIdWithTheCatchAllLast(): void
    {
        $registry = new NotificationTypeRegistry([TestTypes::provider(
            TestTypes::type('b.type', sortOrder: 10),
            TestTypes::type('a.type', sortOrder: 10),
            TestTypes::type('c.type', sortOrder: 5),
        )]);

        $this->assertSame(
            ['c.type', 'a.type', 'b.type', GeneralNotificationType::TYPE_ID],
            array_map(
                static fn (NotificationType $type): string => $type->getTypeId(),
                $registry->getTypes()
            )
        );
    }

    public function testCatchAllIsPresentEvenWithoutProviders(): void
    {
        $registry = new NotificationTypeRegistry([]);

        $this->assertTrue($registry->hasType(GeneralNotificationType::TYPE_ID));
        $this->assertTrue($registry->hasOnlyGeneralType());
        $this->assertTrue(
            $registry->getType(GeneralNotificationType::TYPE_ID)->isSubscriptionLocked(),
            'the catch-all must not be unsubscribable'
        );
    }

    public function testTypesFromSeveralProvidersAreMerged(): void
    {
        $registry = new NotificationTypeRegistry([
            TestTypes::provider(TestTypes::type('a.type')),
            TestTypes::provider(TestTypes::type('b.type')),
        ]);

        $this->assertTrue($registry->hasType('a.type'));
        $this->assertTrue($registry->hasType('b.type'));
    }

    public function testAProviderMayNotClaimTheCatchAllId(): void
    {
        $this->expectException(InvalidNotificationTypeException::class);

        new NotificationTypeRegistry([
            TestTypes::provider(TestTypes::type(GeneralNotificationType::TYPE_ID)),
        ]);
    }

    public function testDuplicateTypeIdIsRejected(): void
    {
        $this->expectException(InvalidNotificationTypeException::class);
        $this->expectExceptionMessageMatches('/registered more than once/');

        new NotificationTypeRegistry([
            TestTypes::provider(TestTypes::type('a.type')),
            TestTypes::provider(TestTypes::type('a.type')),
        ]);
    }

    public function testOverlongTypeIdIsRejected(): void
    {
        $this->expectException(InvalidNotificationTypeException::class);

        new NotificationTypeRegistry([TestTypes::provider(
            TestTypes::type(str_repeat('a', NotificationTypeRegistryInterface::MAX_TYPE_ID_LENGTH + 1))
        )]);
    }

    public function testTypeIdAtExactlyTheLimitIsAccepted(): void
    {
        $typeId = str_repeat('a', NotificationTypeRegistryInterface::MAX_TYPE_ID_LENGTH);

        $registry = new NotificationTypeRegistry([TestTypes::provider(TestTypes::type($typeId))]);

        $this->assertTrue($registry->hasType($typeId));
    }

    public function testUnknownTypeIdThrows(): void
    {
        $this->expectException(NotFoundException::class);

        (new NotificationTypeRegistry([]))->getType('nope.unknown');
    }

    public function testUnknownEmptyAndLegacyTypesResolveToTheCatchAll(): void
    {
        $registry = new NotificationTypeRegistry([TestTypes::provider(TestTypes::type('known.type'))]);

        $this->assertSame(GeneralNotificationType::TYPE_ID, $registry->resolveBucket(null)->getTypeId());
        $this->assertSame(GeneralNotificationType::TYPE_ID, $registry->resolveBucket('')->getTypeId());
        $this->assertSame(GeneralNotificationType::TYPE_ID, $registry->resolveBucket('info')->getTypeId());
        $this->assertSame(
            GeneralNotificationType::TYPE_ID,
            $registry->resolveBucket('some.unregistered.type')->getTypeId()
        );
        $this->assertSame('known.type', $registry->resolveBucket('known.type')->getTypeId());
    }

    public function testHasOnlyGeneralTypeReflectsRegistrationCount(): void
    {
        $this->assertTrue((new NotificationTypeRegistry([]))->hasOnlyGeneralType());
        $this->assertFalse(
            (new NotificationTypeRegistry([TestTypes::provider(TestTypes::type('other.type'))]))
                ->hasOnlyGeneralType()
        );
    }

    public function testHasExternallyDeliverableTypeReflectsTheTypes(): void
    {
        $internalOnly = new NotificationTypeRegistry([TestTypes::provider(
            TestTypes::type('internal.type', allowsExternalDelivery: false)
        )]);
        $this->assertFalse($internalOnly->hasExternallyDeliverableType());

        $withExternal = new NotificationTypeRegistry([TestTypes::provider(
            TestTypes::type('external.type', allowsExternalDelivery: true)
        )]);
        $this->assertTrue($withExternal->hasExternallyDeliverableType());
    }
}
