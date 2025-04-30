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

final readonly class ElementTypes
{
    public const TYPE_ASSET = 'asset';

    public const TYPE_DOCUMENT = 'document';

    public const TYPE_DATA_OBJECT = 'data-object';

    public const TYPE_ELEMENT = 'element';

    public const TYPE_OBJECT = 'object';

    public const TYPE_VARIANT = 'variant';

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
