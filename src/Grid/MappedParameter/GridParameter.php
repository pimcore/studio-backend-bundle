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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;

/**
 * @internal
 */
final readonly class GridParameter
{
    public function __construct(
        private int $folderId,
        private array $gridConfig,
    ) {
    }

    public function getFolderId(): int
    {
        return $this->folderId;
    }

    public function getGridConfig(): array
    {
        return $this->gridConfig;
    }
}
