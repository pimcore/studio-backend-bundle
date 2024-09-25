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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Mapper;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function array_key_exists;

/**
 * @internal
 */
final readonly class ColumnMapper implements ColumnMapperInterface
{
    private const COLUMN_MAPPING = [
        'preview' => 'image',
        'id' => 'integer',
        'type' => 'string',
        'fullpath' => 'string',
        'filename' => 'string',
        'creationDate' => 'datetime',
        'modificationDate' => 'datetime',
        'size' => 'fileSize',
        'key' => 'string',
        'published' => 'boolean',
        'classname' => 'string',
        'index' => 'integer',
    ];

    public function getType(string $column): string
    {
        if (!array_key_exists($column, self::COLUMN_MAPPING)) {
            throw new InvalidArgumentException(sprintf('Column "%s" not supported.', $column));
        }

        return self::COLUMN_MAPPING[$column];
    }
}
