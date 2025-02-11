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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Save Asset Context Permissions',
    required: [
        'hideAdd',
        'addImportFromServer',
        'addUpload',
        'addUploadCompatibility',
        'addUploadFromURL',
        'addUploadZip',
        'addFolder',
        'copy',
        'cut',
        'delete',
        'lock',
        'lockAndPropagate',
        'paste',
        'pasteCut',
        'reload',
        'rename',
        'searchAndMove',
        'unlock',
        'unlockAndPropagate',
    ],
    type: 'object'
)]
class SaveAssetContextPermissions
{
    public function __construct(
        #[Property(description: 'Hide Add Menu', type: 'bool', example: true)]
        private readonly bool $hideAdd = false,
        #[Property(description: 'Add Import From Server', type: 'bool', example: true)]
        private readonly bool $addImportFromServer = true,
        #[Property(description: 'Add Upload', type: 'bool', example: true)]
        private readonly bool $addUpload = true,
        #[Property(description: 'Add Upload Compatibility', type: 'bool', example: true)]
        private readonly bool $addUploadCompatibility = true,
        #[Property(description: 'Add Upload From URL', type: 'bool', example: true)]
        private readonly bool $addUploadFromURL = true,
        #[Property(description: 'Add Upload Zip', type: 'bool', example: true)]
        private readonly bool $addUploadZip = true,
        #[Property(description: 'Add Folder', type: 'bool', example: true)]
        private readonly bool $addFolder = true,
        #[Property(description: 'Copy', type: 'bool', example: true)]
        private readonly bool $copy = true,
        #[Property(description: 'Cut', type: 'bool', example: true)]
        private readonly bool $cut = true,
        #[Property(description: 'Delete', type: 'bool', example: true)]
        private readonly bool $delete = true,
        #[Property(description: 'Lock', type: 'bool', example: true)]
        private readonly bool $lock = true,
        #[Property(description: 'Lock And Propagate', type: 'bool', example: true)]
        private readonly bool $lockAndPropagate = true,
        #[Property(description: 'Paste', type: 'bool', example: true)]
        private readonly bool $paste = true,
        #[Property(description: 'Paste Cut', type: 'bool', example: true)]
        private readonly bool $pasteCut = true,
        #[Property(description: 'Reload', type: 'bool', example: true)]
        private readonly bool $reload = true,
        #[Property(description: 'Rename', type: 'bool', example: true)]
        private readonly bool $rename = true,
        #[Property(description: 'SearchAndMove', type: 'bool', example: true)]
        private readonly bool $searchAndMove = true,
        #[Property(description: 'Unlock', type: 'bool', example: true)]
        private readonly bool $unlock = true,
        #[Property(description: 'Unlock And Propagate', type: 'bool', example: true)]
        private readonly bool $unlockAndPropagate = true,
    ) {
    }

    public function isHideAdd(): bool
    {
        return $this->hideAdd;
    }

    public function isAddImportFromServer(): bool
    {
        return $this->addImportFromServer;
    }

    public function isAddUpload(): bool
    {
        return $this->addUpload;
    }

    public function isAddUploadCompatibility(): bool
    {
        return $this->addUploadCompatibility;
    }

    public function isAddUploadFromURL(): bool
    {
        return $this->addUploadFromURL;
    }

    public function isAddUploadZip(): bool
    {
        return $this->addUploadZip;
    }

    public function isAddFolder(): bool
    {
        return $this->addFolder;
    }

    public function isCopy(): bool
    {
        return $this->copy;
    }

    public function isCut(): bool
    {
        return $this->cut;
    }

    public function isDelete(): bool
    {
        return $this->delete;
    }

    public function isLock(): bool
    {
        return $this->lock;
    }

    public function isLockAndPropagate(): bool
    {
        return $this->lockAndPropagate;
    }

    public function isPaste(): bool
    {
        return $this->paste;
    }

    public function isPasteCut(): bool
    {
        return $this->pasteCut;
    }

    public function isReload(): bool
    {
        return $this->reload;
    }

    public function isRename(): bool
    {
        return $this->rename;
    }

    public function isSearchAndMove(): bool
    {
        return $this->searchAndMove;
    }

    public function isUnlock(): bool
    {
        return $this->unlock;
    }

    public function isUnlockAndPropagate(): bool
    {
        return $this->unlockAndPropagate;
    }
}
