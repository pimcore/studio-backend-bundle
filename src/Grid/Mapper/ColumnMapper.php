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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Mapper;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function array_key_exists;
use function sprintf;

/**
 * @internal
 */
final readonly class ColumnMapper implements ColumnMapperInterface
{
    private const COLUMN_MAPPING = [
        'preview' => 'preview',
        'id' => 'id',
        'type' => 'string',
        'fullpath' => 'string',
        'filename' => 'string',
        'creationDate' => 'datetime',
        'modificationDate' => 'datetime',
        'fileSize' => 'fileSize',
        'key' => 'string',
        'published' => 'boolean',
        'classname' => 'string',
        'index' => 'integer',
        'mimetype' => 'string',
    ];

    public function getType(string $column): string
    {
        if (!array_key_exists($column, self::COLUMN_MAPPING)) {
            throw new InvalidArgumentException(sprintf('Column "%s" not supported.', $column));
        }

        return self::COLUMN_MAPPING[$column];
    }
}
