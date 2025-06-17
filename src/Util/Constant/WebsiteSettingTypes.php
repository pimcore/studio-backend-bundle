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
enum WebsiteSettingTypes: string
{
    use EnumToValueArrayTrait;

    case ASSET = 'asset';

    case OBJECT = 'object';

    case DOCUMENT = 'document';

    case TEXT = 'text';

    case CHECKBOX = 'bool';

    public static function toNameValueArray(): array
    {
        return array_reduce(
            self::cases(),
            static function (array $values, self $case): array {
                $values[ucfirst(strtolower($case->name))] = $case->value;
                return $values;
            },
            []
        );
    }
}
