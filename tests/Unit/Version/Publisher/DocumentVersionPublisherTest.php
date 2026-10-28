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
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Version\Publisher\DocumentVersionPublisher;
use Pimcore\Model\Document\Page;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use ReflectionClass;

/**
 * @internal
 */
final class DocumentVersionPublisherTest extends Unit
{
    private const int USER_ID = 7;

    private const int DOCUMENT_ID = 3;

    /**
     * Publishing a version must write the published state into that version (`existingVersion` save parameter)
     * instead of letting save() create a new one, so the history shows which version went live.
     */
    public function testPublishWritesThePublishedStateIntoTheVersion(): void
    {
        $version = $this->createVersion();

        $currentDocument = $this->createMock(Page::class);
        $currentDocument->method('getKey')->willReturn('current-key');
        $currentDocument->method('getPath')->willReturn('/current/');

        $versionDocument = $this->createMock(Page::class);
        $versionDocument->method('getId')->willReturn(self::DOCUMENT_ID);
        $versionDocument->expects($this->once())->method('setPublished')->with(true);
        $versionDocument->expects($this->once())->method('setKey')->with('current-key');
        $versionDocument->expects($this->once())->method('setPath')->with('/current/');
        $versionDocument->expects($this->once())->method('setUserModification')->with(self::USER_ID);
        $versionDocument->expects($this->once())->method('save')->with(['existingVersion' => $version]);

        $this->createPublisher($currentDocument)->publish($versionDocument, $this->createUser(), $version);
    }

    /**
     * An auto-save version is rewritten by the editor of its user at any time, it is published as a new version
     */
    public function testPublishSavesAnAutoSaveVersionAsANewVersion(): void
    {
        $version = $this->createVersion();
        $version->setAutoSave(true);

        $currentDocument = $this->createMock(Page::class);
        $currentDocument->method('getKey')->willReturn('current-key');
        $currentDocument->method('getPath')->willReturn('/current/');

        $versionDocument = $this->createMock(Page::class);
        $versionDocument->method('getId')->willReturn(self::DOCUMENT_ID);
        $versionDocument->expects($this->once())->method('save')->with([]);

        $this->createPublisher($currentDocument)->publish($versionDocument, $this->createUser(), $version);
    }

    public function testPublishFailsWhenTheDocumentNoLongerExists(): void
    {
        $versionDocument = $this->createMock(Page::class);
        $versionDocument->method('getId')->willReturn(self::DOCUMENT_ID);
        $versionDocument->expects($this->never())->method('save');

        $this->expectException(NotFoundException::class);
        $this->createPublisher(null)->publish($versionDocument, $this->createUser(), $this->createVersion());
    }

    private function createPublisher(?Page $currentDocument): DocumentVersionPublisher
    {
        $documentResolver = $this->createMock(DocumentResolverInterface::class);
        $documentResolver->method('getById')->willReturn($currentDocument);

        return new DocumentVersionPublisher($documentResolver);
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
