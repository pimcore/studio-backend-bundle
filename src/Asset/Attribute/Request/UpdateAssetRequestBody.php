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
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property\UpdateAssetImage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property\UpdateCustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property\UpdateCustomSettingsData;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateIntegerProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateStringProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Attribute\Property\UpdateElementProperties;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class UpdateAssetRequestBody extends RequestBody
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
                            new Property(
                                property: 'coauthorType',
                                description: 'Optional coauthor type stored on versions created by this save '
                                    . '(e.g. agent)',
                                type: 'string',
                                example: 'agent'
                            ),
                            new Property(
                                property: 'coauthor',
                                description: 'Optional coauthor identifier stored on versions created by this save',
                                type: 'string',
                                example: 'product-data-agent'
                            ),
                            new UpdateStringProperty('data'),
                            new UpdateStringProperty('dataUri'),
                            new UpdateCustomMetadata(),
                            new UpdateCustomSettingsData(),
                            new UpdateElementProperties(),
                            new UpdateAssetImage(),
                        ],
                        type: 'object',
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
