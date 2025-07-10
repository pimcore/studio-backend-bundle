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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter;

use Override;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\ConfigurationParameter as GridConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;

/**
 * @internal
 */
final readonly class ConfigurationParameter extends GridConfigurationParameter
{
    /**
     * @return Column[]
     */
    #[Override]
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumnsAsArray(): array
    {
        return array_map(
            static fn (Column $column) => $column->toArray(),
            $this->getColumns()
        );
    }
}
