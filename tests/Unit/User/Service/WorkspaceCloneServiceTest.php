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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\User\Service\WorkspaceCloneService;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\DataObject as DataObjectWorkspace;
use Pimcore\Model\User\Workspace\Document as DocumentWorkspace;

/**
 * @internal
 */
final class WorkspaceCloneServiceTest extends TestCase
{
    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\Service\WorkspaceCloneService::cloneAssetWorkspace
     */
    public function testCloneAssetWorkspace(): void
    {
        $workspace = new AssetWorkspace();
        $workspace->setObjectVar('create', true);

        $workspaceCloneService = new WorkspaceCloneService();
        $clonedWorkspace = $workspaceCloneService->cloneAssetWorkspace($workspace);

        $this->assertInstanceOf(AssetWorkspace::class, $clonedWorkspace);
        $objectVars = $clonedWorkspace->getObjectVars();
        $this->assertTrue($objectVars['create']);
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\Service\WorkspaceCloneService::cloneDocumentWorkspace
     */
    public function testCloneDocumentWorkspace(): void
    {
        $workspace = new DocumentWorkspace();
        $workspace->setObjectVar('create', true);

        $workspaceCloneService = new WorkspaceCloneService();
        $clonedWorkspace = $workspaceCloneService->cloneDocumentWorkspace($workspace);

        $this->assertInstanceOf(DocumentWorkspace::class, $clonedWorkspace);
        $objectVars = $clonedWorkspace->getObjectVars();
        $this->assertTrue($objectVars['create']);
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\Service\WorkspaceCloneService::cloneDataObjectWorkspace
     */
    public function testCloneDataObjectWorkspace(): void
    {
        $workspace = new DataObjectWorkspace();
        $workspace->setObjectVar('create', true);

        $workspaceCloneService = new WorkspaceCloneService();
        $clonedWorkspace = $workspaceCloneService->cloneDataObjectWorkspace($workspace);

        $this->assertInstanceOf(DataObjectWorkspace::class, $clonedWorkspace);
        $objectVars = $clonedWorkspace->getObjectVars();
        $this->assertTrue($objectVars['create']);
    }
}
