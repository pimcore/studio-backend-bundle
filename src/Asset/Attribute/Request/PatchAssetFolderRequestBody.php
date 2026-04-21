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
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property\CustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\PatchCustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Export\Schema\ExportAllFilter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateIntegerProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateStringProperty;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PatchAssetFolderRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['data'],
                properties: [
                    new Property(
                        property: 'data',
                        properties: [
                            new UpdateIntegerProperty('parentId'),
                            new UpdateStringProperty('key'),
                            new UpdateStringProperty('locked'),
                            new CustomMetadata(PatchCustomMetadata::class),
                        ],
                        type: 'object',
                    ),
                    new Property(
                        property: 'filters',
                        ref: ExportAllFilter::class,
                        type: 'object'
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
