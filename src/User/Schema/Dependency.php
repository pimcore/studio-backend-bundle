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
    schema: 'UserDependency',
    title: 'Dependency to an Object',
    description: 'Dependency to an Object',
    required: ['id', 'path', 'subtype'],
    type: 'object'
)]
final readonly class Dependency
{
    public function __construct(
        #[Property(description: 'ID of the object', type: 'integer', example: 42)]
        private int $id,
        #[Property(description: 'Path to the object', type: 'string', example: '/path/to/object')]
        private string $path,
        #[Property(description: 'Subtype of the object', type: 'string', example: 'Car')]
        private string $subtype,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSubtype(): string
    {
        return $this->subtype;
    }
}
