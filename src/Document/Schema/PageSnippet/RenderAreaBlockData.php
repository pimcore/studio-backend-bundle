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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'RenderAreaBlockData',
    title: 'Area block render data for editmode',
    required: ['editableDefinitions', 'htmlCode'],
    type: 'object'
)]
final class RenderAreaBlockData implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(
            description: 'Dynamic array of editable definitions',
            type: 'array',
            items: new Items(type: 'object'),
            example: '[{ "id": "editable_1", "type": "text",' .
            ' "config": { "label": "Text", "defaultValue": "Default text" }, "data": { "text": "Some text" } }]'
        )]
        private readonly array $editableDefinitions = [],
        #[Property(
            description: 'HTML code of the snippet',
            type: 'string',
            example: '<div class="editable" data-key="editable_1">Some text</div>'
        )]
        private readonly string $htmlCode = '',
    ) {
    }

    public function getEditableDefinitions(): array
    {
        return $this->editableDefinitions;
    }

    public function getHtmlCode(): string
    {
        return $this->htmlCode;
    }
}
