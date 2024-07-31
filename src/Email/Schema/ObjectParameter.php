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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'EmailLogObjectParameterData',
    required: ['name', 'value'],
    type: 'object'
)]
final readonly class ObjectParameter
{
    public function __construct(
        #[Property(description: 'id', type: 'int', example: 1020)]
        private int $id,
        #[Property(description: 'type', type: 'string', example: 'object')]
        private string $type,
        #[Property(description: 'class', type: 'string', example: 'AppBundle\\Model\\MyObject')]
        private string $class,
        #[Property(description: 'path', type: 'string', example: '/path/to/object')]
        private string $path,
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
