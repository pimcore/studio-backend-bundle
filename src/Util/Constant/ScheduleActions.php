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

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * @internal
 */
enum ScheduleActions: string
{
    use EnumToValueArrayTrait;

    case PublishVersion = 'publish-version';
    case Publish = 'publish';
    case Unpublish = 'unpublish';
    case Delete = 'delete';

    private const array DEFAULT_ACTIONS = [
        self::Publish,
        self::Unpublish,
        self::Delete,
        self::PublishVersion,
    ];

    private const array ASSET_ACTIONS = [
        self::Delete,
        self::PublishVersion,
    ];

    /**
     * @return self[]
     */
    public static function forElementType(string $elementType): array
    {
        return match ($elementType) {
            ElementTypes::TYPE_ASSET => self::ASSET_ACTIONS,
            ElementTypes::TYPE_DOCUMENT,
            ElementTypes::TYPE_OBJECT,
            ElementTypes::TYPE_DATA_OBJECT => self::DEFAULT_ACTIONS,
            default => [],
        };
    }
}
