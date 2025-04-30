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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Path;

use Attribute;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\FormatTypes;

#[Attribute(Attribute::TARGET_METHOD)]
final class FormatTypeParameter extends PathParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'format',
            description: 'Find asset by matching format type.',
            in: 'path',
            required: true,
            schema: new Schema(
                type: 'string',
                enum: [
                    FormatTypes::OFFICE,
                    FormatTypes::PRINT,
                    FormatTypes::WEB,
                ],
                example: FormatTypes::WEB,
            ),
        );
    }
}
