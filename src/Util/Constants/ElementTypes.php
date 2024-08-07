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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constants;

/**
 * @internal
 */
final readonly class ElementTypes
{
    public const TYPE_ASSET = 'asset';

    public const TYPE_DOCUMENT = 'document';

    public const TYPE_DATA_OBJECT = 'data-object';

    public const TYPE_OBJECT = 'object';

    public const TYPE_ARCHIVE = 'zip archive';

    public const TYPE_FOLDER = 'folder';

    public const TYPE_EMAIL = 'E-Mail';

    public const TYPE_CLASS_DEFINITION = 'class definition';

    public const ALLOWED_TYPES = [
        self::TYPE_DATA_OBJECT,
        self::TYPE_OBJECT,
        self::TYPE_ASSET,
        self::TYPE_DOCUMENT,
    ];
}
