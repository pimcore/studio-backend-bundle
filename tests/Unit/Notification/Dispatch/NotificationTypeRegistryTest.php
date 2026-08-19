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
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\GeneralNotificationDescriptor;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistry;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestNotificationTypeDescriptor;
use function str_repeat;

final class NotificationTypeRegistryTest extends Unit
{
    public function testDescriptorsAreOrderedBySortOrderThenId(): void
    {
        $general = new GeneralNotificationDescriptor();
        $registry = new NotificationTypeRegistry(
            [
                new TestNotificationTypeDescriptor('b.type', sortOrder: 10),
                new TestNotificationTypeDescriptor('a.type', sortOrder: 10),
                new TestNotificationTypeDescriptor('early', sortOrder: 1),
                $general,
            ],
            $general
        );

        $ids = array_map(
            static fn ($descriptor): string => $descriptor->getTypeId(),
            $registry->getDescriptors()
        );

        // The catch-all sorts last by construction: it is the residual bucket.
        $this->assertSame(['early', 'a.type', 'b.type', 'info'], $ids);
    }

    /**
     * Regression: the catch-all was expected to arrive by service tag, so when the tag was not
     * applied a bare installation reported no subscribable types at all and the preferences
     * screen came up empty. Its presence must not depend on wiring.
     */
    public function testCatchAllIsPresentEvenWhenNoDescriptorIsTagged(): void
    {
        $general = new GeneralNotificationDescriptor();
        $registry = new NotificationTypeRegistry([], $general);

        $this->assertTrue($registry->hasDescriptor('info'));
        $this->assertTrue($registry->hasOnlyGeneralDescriptor());
        $this->assertCount(1, $registry->getDescriptors());
    }

    /**
     * It is legitimately reachable twice — added directly and, if a bundle wires it, by tag —
     * which must not trip the duplicate guard.
     */
    public function testCatchAllIsNotDuplicatedWhenAlsoTagged(): void
    {
        $general = new GeneralNotificationDescriptor();
        $registry = new NotificationTypeRegistry([$general], $general);

        $this->assertCount(1, $registry->getDescriptors());
    }

    public function testDuplicateTypeIdIsRejected(): void
    {
        $general = new GeneralNotificationDescriptor();

        $this->expectException(InvalidNotificationTypeException::class);
        $this->expectExceptionMessageMatches('/registered more than once/');

        new NotificationTypeRegistry(
            [
                new TestNotificationTypeDescriptor('duplicate.id'),
                new TestNotificationTypeDescriptor('duplicate.id'),
                $general,
            ],
            $general
        );
    }

    /**
     * The notifications.type column is VARCHAR(20) and MySQL truncates silently outside strict
     * mode, which would turn an over-long id into one matching no descriptor. Failing at boot
     * is the whole point.
     */
    public function testOverlongTypeIdIsRejected(): void
    {
        $general = new GeneralNotificationDescriptor();
        $tooLong = str_repeat('a', NotificationTypeRegistryInterface::MAX_TYPE_ID_LENGTH + 1);

        $this->expectException(InvalidNotificationTypeException::class);
        $this->expectExceptionMessageMatches('/at most 20/');

        new NotificationTypeRegistry([new TestNotificationTypeDescriptor($tooLong), $general], $general);
    }

    public function testTypeIdAtExactlyTheLimitIsAccepted(): void
    {
        $general = new GeneralNotificationDescriptor();
        $exact = str_repeat('a', NotificationTypeRegistryInterface::MAX_TYPE_ID_LENGTH);

        $registry = new NotificationTypeRegistry([new TestNotificationTypeDescriptor($exact), $general], $general);

        $this->assertTrue($registry->hasDescriptor($exact));
    }

    public function testUnknownTypeIdThrows(): void
    {
        $general = new GeneralNotificationDescriptor();
        $registry = new NotificationTypeRegistry([$general], $general);

        $this->expectException(NotFoundException::class);

        $registry->getDescriptor('does.not.exist');
    }

    /**
     * Everything a producer writes without a registered type must land somewhere, or it would
     * have no preference at all.
     */
    public function testUnknownEmptyAndLegacyTypesResolveToTheCatchAll(): void
    {
        $general = new GeneralNotificationDescriptor();
        $registry = new NotificationTypeRegistry(
            [new TestNotificationTypeDescriptor('known.type'), $general],
            $general
        );

        $this->assertSame($general, $registry->resolveBucket(null));
        $this->assertSame($general, $registry->resolveBucket(''));
        $this->assertSame($general, $registry->resolveBucket('info'));
        $this->assertSame($general, $registry->resolveBucket('some.unregistered.type'));
        $this->assertSame('known.type', $registry->resolveBucket('known.type')->getTypeId());
    }

    public function testHasOnlyGeneralDescriptorReflectsRegistrationCount(): void
    {
        $general = new GeneralNotificationDescriptor();

        $alone = new NotificationTypeRegistry([$general], $general);
        $this->assertTrue($alone->hasOnlyGeneralDescriptor());

        $withOthers = new NotificationTypeRegistry(
            [new TestNotificationTypeDescriptor('other.type'), $general],
            $general
        );
        $this->assertFalse($withOthers->hasOnlyGeneralDescriptor());
    }

    public function testHasExternallyDeliverableTypeReflectsTheDescriptors(): void
    {
        $general = new GeneralNotificationDescriptor();

        $internalOnly = new NotificationTypeRegistry(
            [new TestNotificationTypeDescriptor('internal.type', allowsExternalDelivery: false)],
            $general
        );
        $this->assertFalse($internalOnly->hasExternallyDeliverableType());

        $withExternal = new NotificationTypeRegistry(
            [new TestNotificationTypeDescriptor('external.type', allowsExternalDelivery: true)],
            $general
        );
        $this->assertTrue($withExternal->hasExternallyDeliverableType());
    }
}
