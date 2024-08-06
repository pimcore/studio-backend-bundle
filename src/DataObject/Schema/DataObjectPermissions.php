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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;

#[Schema(
    title: 'Data Object Permissions',
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
        #[Property(description: 'Layouts', type: 'string', example: 'default')]
        private ?string $layouts = null,
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

    public function getLayouts(): ?string
    {
        return $this->layouts;
    }
}
