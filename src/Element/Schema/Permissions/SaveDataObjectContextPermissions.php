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
    title: 'Save Data Object Context Permissions',
    required: [
        'add',
        'addFolder',
        'changeChildrenSortBy',
        'copy',
        'cut',
        'delete',
        'lock',
        'lockAndPropagate',
        'paste',
        'publish',
        'refresh',
        'rename',
        'searchAndMove',
        'unlock',
        'unlockAndPropagate',
        'unpublish',
    ],
    type: 'object'
)]
class SaveDataObjectContextPermissions
{
    public function __construct(
        #[Property(description: 'Add', type: 'bool', example: true)]
        private readonly bool $add = true,
        #[Property(description: 'Add Folder', type: 'bool', example: true)]
        private readonly bool $addFolder = true,
        #[Property(description: 'Change Children SortBy', type: 'bool', example: true)]
        private readonly bool $changeChildrenSortBy = true,
        #[Property(description: 'Copy', type: 'bool', example: true)]
        private readonly bool $copy = true,
        #[Property(description: 'Cut', type: 'bool', example: true)]
        private readonly bool $cut = true,
        #[Property(description: 'Delete', type: 'bool', example: true)]
        private readonly bool $delete = true,
        #[Property(description: 'Lock', type: 'bool', example: true)]
        private readonly bool $lock = true,
        #[Property(description: 'Lock and Propagate', type: 'bool', example: true)]
        private readonly bool $lockAndPropagate = true,
        #[Property(description: 'Paste', type: 'bool', example: true)]
        private readonly bool $paste = true,
        #[Property(description: 'Publish', type: 'bool', example: true)]
        private readonly bool $publish = true,
        #[Property(description: 'Refresh', type: 'bool', example: true)]
        private readonly bool $refresh = true,
        #[Property(description: 'Rename', type: 'bool', example: true)]
        private readonly bool $rename = true,
        #[Property(description: 'Search and Move', type: 'bool', example: true)]
        private readonly bool $searchAndMove = true,
        #[Property(description: 'Unlock', type: 'bool', example: true)]
        private readonly bool $unlock = true,
        #[Property(description: 'Unlock and Propagate', type: 'bool', example: true)]
        private readonly bool $unlockAndPropagate = true,
        #[Property(description: 'Unpublish', type: 'bool', example: true)]
        private readonly bool $unpublish = true,
    ) {
    }

    public function isAdd(): bool
    {
        return $this->add;
    }

    public function isAddFolder(): bool
    {
        return $this->addFolder;
    }

    public function isChangeChildrenSortBy(): bool
    {
        return $this->changeChildrenSortBy;
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

    public function isPublish(): bool
    {
        return $this->publish;
    }

    public function isRefresh(): bool
    {
        return $this->refresh;
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

    public function isUnpublish(): bool
    {
        return $this->unpublish;
    }
}
