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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class AddAssetsRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new MediaType(
                mediaType: 'multipart/form-data',
                schema: new Schema(
                    required: ['files[]'],
                    properties: [
                        new Property(
                            property: 'files[]',
                            description: 'Files to upload',
                            type: 'array',
                            items: new Items(
                                title: 'file',
                                type: 'string',
                                format: 'binary',
                            ),
                        ),
                    ],
                    type: 'object',
                )
            ),
        );
    }
}
