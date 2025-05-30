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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'DocTypeType',
    title: 'DocType Type',
    required: [
        'id', 'name', 'type', 'group', 'controller', 'template', 'priority',
        'creationDate', 'modificationDate', 'staticGeneratorEnabled', 'writeable',
    ],
    type: 'object'
)]
final class DocTypeType implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'page')]
        private readonly string $name,
        #[Property(description: 'Valid table', type: 'string', example: 'page')]
        private readonly string $validTable,
        #[Property(description: 'Children supported', type: 'boolean', example: false)]
        private readonly bool $childrenSupported = false,
        #[Property(description: 'Direct route', type: 'boolean', example: false)]
        private readonly bool $directRoute = false,
        #[Property(description: 'Predefined document types', type: 'boolean', example: false)]
        private readonly bool $predefinedDocumentTypes = false,
        #[Property(description: 'Translatable', type: 'boolean', example: false)]
        private readonly bool $translatable = false,
        #[Property(description: 'Translatable Inheritance', type: 'boolean', example: false)]
        private readonly bool $translatableInheritance = false,
        #[Property(description: 'Only printable children', type: 'boolean', example: false)]
        private readonly bool $onlyPrintableChildren = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValidTable(): string
    {
        return $this->validTable;
    }

    public function isChildrenSupported(): bool
    {
        return $this->childrenSupported;
    }

    public function isDirectRoute(): bool
    {
        return $this->directRoute;
    }

    public function isPredefinedDocumentTypes(): bool
    {
        return $this->predefinedDocumentTypes;
    }

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    public function isTranslatableInheritance(): bool
    {
        return $this->translatableInheritance;
    }

    public function isOnlyPrintableChildren(): bool
    {
        return $this->onlyPrintableChildren;
    }
}
