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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

/**
 * Mirrors the column widths of `versions`.`coauthorType` and `versions`.`coauthor` in core, so
 * overlong input is rejected at the API boundary instead of being truncated during the save.
 *
 * @internal
 */
final class VersionCoauthor
{
    public const int MAX_TYPE_LENGTH = 50;

    public const int MAX_COAUTHOR_LENGTH = 255;
}
