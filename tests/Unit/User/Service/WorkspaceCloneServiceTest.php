<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\User\Service\WorkspaceCloneService;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\DataObject as DataObjectWorkspace;
use Pimcore\Model\User\Workspace\Document as DocumentWorkspace;

/**
 * @internal
 */
final class WorkspaceCloneServiceTest extends Unit
{
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
