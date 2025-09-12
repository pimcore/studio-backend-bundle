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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'User Document Workspace',
    required: ['save', 'unpublish'],
    type: 'object'
)]
final readonly class UserDocumentWorkspace extends UserWorkspace
{
    public function __construct(
        #[Property(description: 'Save', type: 'bool', example: true)]
        private bool $save,
        #[Property(description: 'Unpublish', type: 'bool', example: true)]
        private bool $unpublish,
        private int $cid,
        private string $cpath,
        private bool $list,
        private bool $view,
        private bool $publish,
        private bool $delete,
        private bool $rename,
        private bool $create,
        private bool $settings,
        private bool $versions,
        private bool $properties
    ) {
        parent::__construct(
            $this->cid,
            $this->cpath,
            $this->list,
            $this->view,
            $this->publish,
            $this->delete,
            $this->rename,
            $this->create,
            $this->settings,
            $this->versions,
            $this->properties
        );
    }

    public function isSave(): bool
    {
        return $this->save;
    }

    public function isUnpublish(): bool
    {
        return $this->unpublish;
    }
}
