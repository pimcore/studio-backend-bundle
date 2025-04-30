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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Exception;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\DataObject as DataObjectWorkspace;
use Pimcore\Model\User\Workspace\Document as DocumentWorkspace;

/**
 * @internal
 */
interface WorkspaceCloneServiceInterface
{
    /**
     * @throws Exception
     */
    public function cloneAssetWorkspace(AssetWorkspace $workspace): AssetWorkspace;

    /**
     * @throws Exception
     */
    public function cloneDocumentWorkspace(DocumentWorkspace $workspace): DocumentWorkspace;

    /**
     * @throws Exception
     */
    public function cloneDataObjectWorkspace(DataObjectWorkspace $workspace): DataObjectWorkspace;
}
