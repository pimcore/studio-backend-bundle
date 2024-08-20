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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset;

/**
 * @internal
 */
final readonly class ResizeModes
{
    public const RESIZE = 'resize';

    public const SCALE_BY_WIDTH = 'scaleByWidth';

    public const SCALE_BY_HEIGHT = 'scaleByHeight';

    public const ALLOWED_MODES = [
        self::RESIZE,
        self::SCALE_BY_WIDTH,
        self::SCALE_BY_HEIGHT,
    ];
}
