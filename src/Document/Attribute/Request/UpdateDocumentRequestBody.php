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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateBooleanProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateIntegerProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateObjectProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateStringProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Attribute\Property\UpdateElementProperties;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentFieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class UpdateDocumentRequestBody extends RequestBody
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
                            new UpdateIntegerProperty('parentId', 1),
                            new UpdateIntegerProperty('index', 0),
                            new UpdateStringProperty('key'),
                            new Property(property:'task', type: 'string', enum: ElementSaveTasks::values()),
                            new UpdateStringProperty('locked'),
                            new UpdateBooleanProperty('published'),
                            new UpdateObjectProperty(DocumentFieldKeys::EDITABLE_DATA->value),
                            new UpdateObjectProperty(DocumentFieldKeys::SETTINGS_DATA->value),
                            new UpdateBooleanProperty(DocumentFieldKeys::MISSING_REQUIRED_EDITABLE->value),
                            new UpdateElementProperties(),
                        ],
                        type: 'object',
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
