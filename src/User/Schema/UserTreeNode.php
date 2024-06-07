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
    title: 'User Tree Node',
    description: 'One node in the user tree',
    type: 'object'
)]
final readonly class UserTreeNode
{
    public function __construct(
        #[Property(description: 'Unique Identifier', type: 'integer', example: '1')]
        private int $id,
        #[Property(description: 'Name of Folder or User', type: 'string', example: 'admin')]
        private string $name,
        #[Property(description: 'Is ether user or folder', type: 'string', example: 'user')]
        private string $type,
        #[Property(description: 'If a folder has sub items', type: 'bool', example: true)]
        private bool $hasChildren,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function hasChildren(): bool
    {
        return $this->hasChildren;
    }
}
