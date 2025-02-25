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
    title: 'Save Document Context Permissions',
    required: [
        'add',
        'addEmail',
        'addFolder',
        'addHardlink',
        'addHeadlessDocument',
        'addLink',
        'addNewsletter',
        'addPrintPage',
        'addSnippet',
        'convert',
        'copy',
        'cut',
        'delete',
        'editSite',
        'lock',
        'lockAndPropagate',
        'open',
        'paste',
        'pasteCut',
        'publish',
        'refresh',
        'removeSite',
        'rename',
        'searchAndMove',
        'unlock',
        'unlockAndPropagate',
        'unpublish',
        'useAsSite',
    ],
    type: 'object'
)]
class SaveDocumentContextPermissions
{
    public function __construct(
        #[Property(description: 'Add', type: 'bool', example: true)]
        private readonly bool $add = true,
        #[Property(description: 'Add E-Mail', type: 'bool', example: true)]
        private readonly bool $addEmail = true,
        #[Property(description: 'Add Folder', type: 'bool', example: true)]
        private readonly bool $addFolder = true,
        #[Property(description: 'Add Hardlink', type: 'bool', example: true)]
        private readonly bool $addHardlink = true,
        #[Property(description: 'Add Headless Document', type: 'bool', example: true)]
        private readonly bool $addHeadlessDocument = true,
        #[Property(description: 'Add Link', type: 'bool', example: true)]
        private readonly bool $addLink = true,
        #[Property(description: 'Add Newsletter', type: 'bool', example: true)]
        private readonly bool $addNewsletter = true,
        #[Property(description: 'Add Print Page', type: 'bool', example: true)]
        private readonly bool $addPrintPage = true,
        #[Property(description: 'Add Snippet', type: 'bool', example: true)]
        private readonly bool $addSnippet = true,
        #[Property(description: 'Convert', type: 'bool', example: true)]
        private readonly bool $convert = true,
        #[Property(description: 'Copy', type: 'bool', example: true)]
        private readonly bool $copy = true,
        #[Property(description: 'Cut', type: 'bool', example: true)]
        private readonly bool $cut = true,
        #[Property(description: 'Delete', type: 'bool', example: true)]
        private readonly bool $delete = true,
        #[Property(description: 'Edit Site', type: 'bool', example: true)]
        private readonly bool $editSite = true,
        #[Property(description: 'Lock', type: 'bool', example: true)]
        private readonly bool $lock = true,
        #[Property(description: 'Lock and Propagate', type: 'bool', example: true)]
        private readonly bool $lockAndPropagate = true,
        #[Property(description: 'Open', type: 'bool', example: true)]
        private readonly bool $open = true,
        #[Property(description: 'Paste', type: 'bool', example: true)]
        private readonly bool $paste = true,
        #[Property(description: 'Paste Cut', type: 'bool', example: true)]
        private readonly bool $pasteCut = true,
        #[Property(description: 'Publish', type: 'bool', example: true)]
        private readonly bool $publish = true,
        #[Property(description: 'Refresh', type: 'bool', example: true)]
        private readonly bool $refresh = true,
        #[Property(description: 'Remove Site', type: 'bool', example: true)]
        private readonly bool $removeSite = true,
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
        #[Property(description: 'Use As Site', type: 'bool', example: true)]
        private readonly bool $useAsSite = true,
    ) {
    }

    public function isAdd(): bool
    {
        return $this->add;
    }

    public function isAddEmail(): bool
    {
        return $this->addEmail;
    }

    public function isAddFolder(): bool
    {
        return $this->addFolder;
    }

    public function isAddHardlink(): bool
    {
        return $this->addHardlink;
    }

    public function isAddHeadlessDocument(): bool
    {
        return $this->addHeadlessDocument;
    }

    public function isAddLink(): bool
    {
        return $this->addLink;
    }

    public function isAddNewsletter(): bool
    {
        return $this->addNewsletter;
    }

    public function isAddPrintPage(): bool
    {
        return $this->addPrintPage;
    }

    public function isAddSnippet(): bool
    {
        return $this->addSnippet;
    }

    public function isConvert(): bool
    {
        return $this->convert;
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

    public function isEditSite(): bool
    {
        return $this->editSite;
    }

    public function isLock(): bool
    {
        return $this->lock;
    }

    public function isLockAndPropagate(): bool
    {
        return $this->lockAndPropagate;
    }

    public function isOpen(): bool
    {
        return $this->open;
    }

    public function isPaste(): bool
    {
        return $this->paste;
    }

    public function isPasteCut(): bool
    {
        return $this->pasteCut;
    }

    public function isPublish(): bool
    {
        return $this->publish;
    }

    public function isRefresh(): bool
    {
        return $this->refresh;
    }

    public function isRemoveSite(): bool
    {
        return $this->removeSite;
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

    public function isUseAsSite(): bool
    {
        return $this->useAsSite;
    }
}
