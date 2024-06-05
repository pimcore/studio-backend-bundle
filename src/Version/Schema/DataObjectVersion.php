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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'DataObjectVersion',
    required: ['modificationDate', 'path', 'published'],
    type: 'object'
)]
final class DataObjectVersion implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'modification date', type: 'integer', example: 1712823182)]
        private readonly int $modificationDate,
        #[Property(description: 'path', type: 'string', example: '/path/to/object')]
        private readonly string $path,
        #[Property(description: 'published', type: 'bool', example: true)]
        private readonly bool $published
    ) {

    }

    public function getModificationDate(): int
    {
        return $this->modificationDate;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }
}
