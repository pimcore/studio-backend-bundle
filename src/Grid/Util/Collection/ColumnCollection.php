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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection;

use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;

/**
 * @internal
 */
final readonly class ColumnCollection
{
    /**
     * @param array<int, Column> $columns
     */
    public function __construct(
        private array $columns,
    ) {
    }

    /**
     * @return array<int, Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
