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
