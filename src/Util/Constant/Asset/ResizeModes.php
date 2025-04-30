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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset;

/**
 * @internal
 */
final readonly class ResizeModes
{
    public const RESIZE = 'resize';

    public const SCALE_BY_WIDTH = 'scaleByWidth';

    public const SCALE_BY_HEIGHT = 'scaleByHeight';

    public const NONE = 'none';

    public const ALLOWED_MODES = [
        self::RESIZE,
        self::SCALE_BY_WIDTH,
        self::SCALE_BY_HEIGHT,
    ];
}
