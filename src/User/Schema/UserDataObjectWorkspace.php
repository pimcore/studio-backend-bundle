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
final readonly class UserDataObjectWorkspace extends UserWorkspace
{
    public function __construct(
        #[Property(description: 'Save', type: 'bool', example: true)]
        private bool $save,
        #[Property(description: 'Unpublish', type: 'bool', example: true)]
        private bool $unpublish,
        #[Property(description: 'Localized Edit', type: 'string', example: 'default')]
        private ?string $localizedEdit,
        #[Property(description: 'Localized View', type: 'string', example: 'default')]
        private ?string $localizedView,
        #[Property(description: 'Layouts', type: 'string', example: 'CAR')]
        private ?string $layouts,
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

    public function getLocalizedEdit(): ?string
    {
        return $this->localizedEdit;
    }

    public function getLocalizedView(): ?string
    {
        return $this->localizedView;
    }

    public function getLayouts(): ?string
    {
        return $this->layouts;
    }
}
