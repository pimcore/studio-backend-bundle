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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'TextLayoutPreview',
    title: 'Text Layout Preview Data',
    required: [
        'className',
        'path',
        'renderingData',
        'renderingClass',
        'html',
    ],
    type: 'object'
)]
final class TextLayoutPreview implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Name of class definition', type: 'string', example: 'Car')]
        private readonly string $className,
        #[Property(description: 'Path of the data object for preview', type: 'string', example: '/cars/my-car')]
        private readonly ?string $path = null,
        #[Property(description: 'Data for preview', type: 'string', example: '{"field1":"value1","field2":"value2"}')]
        private readonly ?string $renderingData = null,
        #[Property(description: 'Rendering class for preview', type: 'string', example: 'App\\DataObject\\Car')]
        private readonly ?string $renderingClass = null,
        #[Property(description: 'HTML preview of the layout', type: 'string', example: '<div>...</div>')]
        private readonly ?string $html = null
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getRenderingData(): ?string
    {
        return $this->renderingData;
    }

    public function getRenderingClass(): ?string
    {
        return $this->renderingClass;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }
}
