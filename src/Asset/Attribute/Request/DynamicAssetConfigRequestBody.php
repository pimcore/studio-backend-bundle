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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class DynamicAssetConfigRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['dynamicConfig'],
                properties: [
                    new Property(
                        property: 'dynamicConfig',
                        type: 'object',
                        example:
                        '{
                            "alt": "",
                            "cropPercent": false,
                            "cropWidth": 0,
                            "cropHeight": 0,
                            "cropTop": 0,
                            "cropLeft": 0,
                            "thumbnail": {
                                "width": 200,
                                "height": 200,
                                "interlace": true,
                                "quality": 90
                            }
                        }'
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
