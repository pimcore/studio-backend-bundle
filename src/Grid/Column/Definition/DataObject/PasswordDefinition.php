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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject;

use Override;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FrontendType;

/**
 * @internal
 */
final readonly class PasswordDefinition extends AbstractDefinition
{
    public function getType(): string
    {
        return 'data-object.password';
    }

    public function getFrontendType(): string
    {
        return 'password';
    }

    #[Override]
    public function isSortable(): bool
    {
        return false;
    }

    #[Override]
    public function isFilterable(): bool
    {
        return false;
    }

    #[Override]
    public function isExportable(): bool
    {
        return false;
    }
}
