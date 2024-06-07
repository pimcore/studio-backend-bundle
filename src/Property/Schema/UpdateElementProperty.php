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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'UpdateDataProperty',
    required: ['key', 'data', 'type', 'inheritable'],
    type: 'object'
)]
final readonly class UpdateElementProperty
{
    public function __construct(
        #[Property(description: 'key', type: 'string', example: 'Mister Proper')]
        private string $key,
        #[Property(description: 'data', type: 'mixed', example: '123')]
        private mixed $data,
        #[Property(description: 'type', type: 'string', example: 'document')]
        private string $type,
        #[Property(description: 'inheritable', type: 'boolean', example: false)]
        private bool $inheritable,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getInheritable(): bool
    {
        return $this->inheritable;
    }
}
