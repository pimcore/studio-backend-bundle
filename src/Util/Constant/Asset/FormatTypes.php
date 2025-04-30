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
final readonly class FormatTypes
{
    public const OFFICE = 'office';

    public const PRINT = 'print';

    public const WEB = 'web';

    public const SOURCE = 'source';

    public const ALLOWED_FORMATS = [
        self::OFFICE,
        self::PRINT,
        self::WEB,
    ];
}
