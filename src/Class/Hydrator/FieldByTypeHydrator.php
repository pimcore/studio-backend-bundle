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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldByType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;

/**
 * @internal
 */
final readonly class FieldByTypeHydrator implements FieldByTypeHydratorInterface
{
    public function hydrate(string $key): FieldByType
    {
        return new FieldByType($key);
    }

    public function resolveFieldKey(ColumnConfiguration $column): string
    {
        return $column->getConfig()['field'] ?? $column->getKey();
    }
}
