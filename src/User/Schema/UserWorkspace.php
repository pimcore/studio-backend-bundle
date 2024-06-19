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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'User Workspace',
    description: 'Workspace of a user',
    required: [
        'cid', 'cpath', 'list', 'view',
        'publish', 'delete', 'rename',
        'create', 'settings', 'versions',
        'properties'
    ],
    type: 'object',
)]
final readonly class UserWorkspace
{
    public function __construct(
        #[Property(description: 'ID of the element', type: 'integer', example: '1')]
        private int $cid,
        #[Property(description: 'Path of the element', type: 'string', example: '/path/to/element')]
        private string $cpath,
        #[Property(description: 'List Permission', type: 'boolean', example: true)]
        private bool $list,
        #[Property(description: 'View Permission', type: 'boolean', example: true)]
        private bool $view,
        #[Property(description: 'Publish Permission', type: 'boolean', example: true)]
        private bool $publish,
        #[Property(description: 'Delete Permission', type: 'boolean', example: true)]
        private bool $delete,
        #[Property(description: 'Rename Permission', type: 'boolean', example: true)]
        private bool $rename,
        #[Property(description: 'Create Permission', type: 'boolean', example: true)]
        private bool $create,
        #[Property(description: 'Settings Permission', type: 'boolean', example: true)]
        private bool $settings,
        #[Property(description: 'Versions Permission', type: 'boolean', example: true)]
        private bool $versions,
        #[Property(description: 'Properties Permission', type: 'boolean', example: true)]
        private bool $properties,
    ) {
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function getCpath(): string
    {
        return $this->cpath;
    }

    public function hasList(): bool
    {
        return $this->list;
    }

    public function hasView(): bool
    {
        return $this->view;
    }

    public function hasPublish(): bool
    {
        return $this->publish;
    }

    public function hasDelete(): bool
    {
        return $this->delete;
    }

    public function hasRename(): bool
    {
        return $this->rename;
    }

    public function hasCreate(): bool
    {
        return $this->create;
    }

    public function hasSettings(): bool
    {
        return $this->settings;
    }

    public function hasVersions(): bool
    {
        return $this->versions;
    }

    public function hasProperties(): bool
    {
        return $this->properties;
    }
}
