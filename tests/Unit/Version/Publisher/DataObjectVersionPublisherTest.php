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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Version\Publisher;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementPublishingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Version\Publisher\DataObjectVersionPublisher;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use ReflectionClass;

/**
 * @internal
 */
final class DataObjectVersionPublisherTest extends Unit
{
    private const int USER_ID = 7;

    /**
     * Publishing a version must write the published state into that version (`existingVersion` save parameter)
     * instead of letting save() create a new one, so the history shows which version went live.
     */
    public function testPublishWritesThePublishedStateIntoTheVersion(): void
    {
        $version = $this->createVersion();

        $dataObject = $this->createMock(Concrete::class);
        $dataObject->expects($this->once())->method('setPublished')->with(true);
        $dataObject->expects($this->once())->method('setUserModification')->with(self::USER_ID);
        $dataObject->expects($this->once())->method('save')->with(['existingVersion' => $version]);

        (new DataObjectVersionPublisher())->publish($dataObject, $this->createUser(), $version);
    }

    public function testPublishIgnoresElementsWithoutVersions(): void
    {
        $folder = $this->createMock(Folder::class);
        $folder->expects($this->never())->method('save');

        (new DataObjectVersionPublisher())->publish($folder, $this->createUser(), $this->createVersion());
    }

    public function testFailedSaveIsReportedAsPublishingFailure(): void
    {
        $dataObject = $this->createMock(Concrete::class);
        $dataObject->method('getId')->willReturn(5);
        $dataObject->method('save')->willThrowException(new Exception('save failed'));

        $this->expectException(ElementPublishingFailedException::class);
        (new DataObjectVersionPublisher())->publish($dataObject, $this->createUser(), $this->createVersion());
    }

    private function createUser(): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(self::USER_ID);

        return $user;
    }

    private function createVersion(): Version
    {
        // Version's constructor pulls its services from the container
        return (new ReflectionClass(Version::class))->newInstanceWithoutConstructor();
    }
}
