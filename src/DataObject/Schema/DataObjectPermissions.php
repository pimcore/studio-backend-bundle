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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;

#[Schema(
    title: 'Data Object Permissions',
    required: ['save', 'unpublish', 'localizedEdit', 'localizedView'],
    type: 'object'
)]
final readonly class DataObjectPermissions extends Permissions
{
    public function __construct(
        #[Property(description: 'Save', type: 'bool', example: true)]
        private bool $save = true,
        #[Property(description: 'Unpublish', type: 'bool', example: true)]
        private bool $unpublish = true,
        #[Property(description: 'Localized Edit', type: 'string', example: 'default')]
        private ?string $localizedEdit = null,
        #[Property(description: 'Localized View', type: 'string', example: 'default')]
        private ?string $localizedView = null,
        private bool $list = true,
        private bool $view = true,
        private bool $publish = true,
        private bool $delete = true,
        private bool $rename = true,
        private bool $create = true,
        private bool $settings = true,
        private bool $versions = true,
        private bool $properties = true
    ) {
        parent::__construct(
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
}
