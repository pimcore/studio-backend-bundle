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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Document\Service\ExecutionEngine;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\ExecutionEngine\CloneService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Folder;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class CloneServiceTest extends Unit
{
    private const int ORIGINAL_PARENT_ID = 10;

    private const int NEW_PARENT_ID = 20;

    /**
     * @dataProvider clonePathProvider
     *
     * @throws Exception
     */
    public function testGetNewCloneTargetResolvesThePathInsideTheClone(
        string $originalParentPath,
        string $newParentPath,
        string $sourceRealPath,
        string $expectedLookupPath
    ): void {
        $lookedUpPath = null;
        $service = $this->getCloneService(
            $originalParentPath,
            $newParentPath,
            $lookedUpPath
        );

        $service->getNewCloneTarget(
            $this->makeEmpty(UserInterface::class),
            $this->makeEmpty(Document::class, ['getRealPath' => $sourceRealPath]),
            self::ORIGINAL_PARENT_ID,
            self::NEW_PARENT_ID
        );

        $this->assertSame($expectedLookupPath, $lookedUpPath);
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public function clonePathProvider(): array
    {
        return [
            'direct child' => [
                '/source',
                '/target/source',
                '/source/',
                '/target/source',
            ],
            'nested sub folders' => [
                '/source',
                '/target/source',
                '/source/sub1/subsub1/deep1/',
                '/target/source/sub1/subsub1/deep1',
            ],
            'copy next to the source' => [
                '/source',
                '/source_copy',
                '/source/sub1/',
                '/source_copy/sub1',
            ],
            'parentheses and brackets in the names' => [
                '/Brand (2024)',
                '/target/Brand (2024)',
                '/Brand (2024)/Sub (a)/Deep [x]/',
                '/target/Brand (2024)/Sub (a)/Deep [x]',
            ],
            'at sign in the names' => [
                '/logo@2x',
                '/target/logo@2x',
                '/logo@2x/sub/',
                '/target/logo@2x/sub',
            ],
            'regex anchors and dots in the names' => [
                '/v1.0^final$',
                '/target/v1.0^final$',
                '/v1.0^final$/a.b/',
                '/target/v1.0^final$/a.b',
            ],
            'name repeated further down the tree' => [
                '/source',
                '/target/source',
                '/source/source/source/',
                '/target/source/source/source',
            ],
        ];
    }

    /**
     * @throws Exception
     */
    private function getCloneService(
        string $originalParentPath,
        string $newParentPath,
        ?string &$lookedUpPath
    ): CloneService {
        $elements = [
            self::ORIGINAL_PARENT_ID => $this->makeFolder($originalParentPath),
            self::NEW_PARENT_ID => $this->makeFolder($newParentPath),
        ];

        $elementService = $this->makeEmpty(DocumentServiceInterface::class, [
            'getDocumentElement' => static fn (UserInterface $user, int $id): Document => $elements[$id],
            'getDocumentElementByPath' => function (UserInterface $user, string $path) use (&$lookedUpPath): Document {
                $lookedUpPath = $path;

                return $this->makeFolder($path);
            },
        ]);

        return new CloneService(
            $elementService,
            $this->makeEmpty(DocumentSearchServiceInterface::class),
            $this->makeEmpty(JobExecutionAgentInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(SynchronousProcessingServiceInterface::class)
        );
    }

    /**
     * @throws Exception
     */
    private function makeFolder(string $fullPath): Folder
    {
        return $this->makeEmpty(Folder::class, [
            'getRealFullPath' => $fullPath,
            'getFullPath' => $fullPath,
            '__toString' => $fullPath,
        ]);
    }
}
