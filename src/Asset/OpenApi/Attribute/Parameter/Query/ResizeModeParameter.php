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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\ResizeModes;

#[Attribute(Attribute::TARGET_METHOD)]
final class ResizeModeParameter extends QueryParameter
{
    public function __construct(
        bool $required = true,
        array $resizeModes = ResizeModes::ALLOWED_MODES,
        string $defaultValue = ResizeModes::SCALE_BY_WIDTH
    ) {
        parent::__construct(
            name: 'resizeMode',
            description: 'Resize mode of downloaded image.',
            in: 'query',
            required: $required,

            schema: new Schema(
                type: 'string',
                enum: $resizeModes,
                example: $defaultValue
            ),
        );
    }
}
