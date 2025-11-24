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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function array_key_exists;
use function sprintf;

final readonly class GdprDataRow
{
    /**
     * @param array<string, mixed> $data
     * @param GdprDataColumn[] $availableColumns
     */
    public function __construct(
        private array $data,
        array $availableColumns
    ) {
        foreach ($availableColumns as $column) {
            $columnName = $column->getKey();
            if (!array_key_exists($columnName, $data)) {
                throw new InvalidArgumentException(sprintf(
                    'Missing required column "%s"',
                    $columnName
                ));
            }
        }
    }

    public function getData(): array
    {
        return $this->data;
    }
}
