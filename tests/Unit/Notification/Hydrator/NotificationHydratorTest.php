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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Hydrator;

use Codeception\Test\Unit;
use DateTimeImmutable;
use DateTimeZone;
use Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator\NotificationHydrator;
use Pimcore\Model\Notification as NotificationModel;
use function date_default_timezone_get;
use function date_default_timezone_set;

/**
 * @internal
 */
final class NotificationHydratorTest extends Unit
{
    private const CREATION_DATE = '2026-07-29 09:57:48';

    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    /**
     * Notification\Dao::save() writes creationDate as a naive wall-clock string in the
     * application timezone, so hydrating it must not assume the string is UTC.
     */
    public function testCreationDateIsParsedInApplicationTimezoneDuringDst(): void
    {
        date_default_timezone_set('Europe/Berlin');

        $hydrator = new NotificationHydrator();
        $model = $this->createNotificationModel();

        $expected = $this->expectedTimestamp('Europe/Berlin');

        $this->assertSame($expected, $hydrator->hydrate($model)->getCreationDate());
        $this->assertSame($expected, $hydrator->hydrateMinimal($model)->getCreationDate());
        $this->assertSame($expected, $hydrator->hydrateDetail($model)->getCreationDate());

        // Guard against the regression: reading the local string as UTC shifted it by +2h.
        $this->assertNotSame($this->expectedTimestamp('UTC'), $expected);
    }

    public function testCreationDateIsParsedInApplicationTimezoneOutsideDst(): void
    {
        date_default_timezone_set('Europe/Berlin');

        $hydrator = new NotificationHydrator();
        $model = $this->createNotificationModel('2026-01-15 09:57:48');

        $expected = (new DateTimeImmutable('2026-01-15 09:57:48', new DateTimeZone('Europe/Berlin')))
            ->getTimestamp();

        $this->assertSame($expected, $hydrator->hydrate($model)->getCreationDate());
    }

    public function testCreationDateIsUnchangedForUtcApplicationTimezone(): void
    {
        date_default_timezone_set('UTC');

        $hydrator = new NotificationHydrator();
        $model = $this->createNotificationModel();

        $this->assertSame(
            $this->expectedTimestamp('UTC'),
            $hydrator->hydrate($model)->getCreationDate()
        );
    }

    private function expectedTimestamp(string $timezone): int
    {
        return (new DateTimeImmutable(self::CREATION_DATE, new DateTimeZone($timezone)))->getTimestamp();
    }

    private function createNotificationModel(string $creationDate = self::CREATION_DATE): NotificationModel
    {
        $model = new NotificationModel();
        $model->setId(221189);
        $model->setType('info');
        $model->setTitle('Notification title');
        $model->setMessage('Notification message');
        $model->setCreationDate($creationDate);

        return $model;
    }
}
