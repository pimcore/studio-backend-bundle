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
    public const string  TYPE_ASSET = 'asset';

    public const string  TYPE_DOCUMENT = 'document';

    public const string  TYPE_DATA_OBJECT = 'data-object';

    public const string  TYPE_ELEMENT = 'element';

    public const string  TYPE_OBJECT = 'object';

    public const string  TYPE_VARIANT = 'variant';

    public const string  TYPE_ARCHIVE = 'zip archive';

    public const string  TYPE_FOLDER = 'folder';

    public const string TYPE_EMAIL = 'E-Mail';

    public const string TYPE_CLASS_DEFINITION = 'class definition';

    public const string DOC_TYPE = 'docType';

    public const string CLASS_TYPE = 'class';

    public const array ALLOWED_TYPES = [
        self::TYPE_DATA_OBJECT,
        self::TYPE_OBJECT,
        self::TYPE_ASSET,
        self::TYPE_DOCUMENT,
    ];

    public const array ALLOWED_STUDIO_TYPES = [
        self::TYPE_DATA_OBJECT,
        self::TYPE_ASSET,
        self::TYPE_DOCUMENT,
    ];
}
