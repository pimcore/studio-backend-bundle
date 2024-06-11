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


namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Exception;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\Document as DocumentWorkspace;
use Pimcore\Model\User\Workspace\DataObject as DataObjectWorkspace;

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