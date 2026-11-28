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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use function in_array;
use function is_string;

/**
 * The allowed-types settings of a many-to-many relation or advanced many-to-many relation as the class
 * editor holds them. They are not saved yet, so the visible fields they offer are derived from them
 * instead of from a stored class definition.
 */
#[Schema(
    schema: 'RelationVisibleFieldsConfiguration',
    title: 'Relation Visible Fields Configuration',
    type: 'object'
)]
final readonly class RelationVisibleFieldsConfiguration
{
    public const string TYPE_MANY_TO_MANY_RELATION = 'manyToManyRelation';

    public const string TYPE_ADVANCED_MANY_TO_MANY_RELATION = 'advancedManyToManyRelation';

    public const array SUPPORTED_TYPES = [
        self::TYPE_MANY_TO_MANY_RELATION,
        self::TYPE_ADVANCED_MANY_TO_MANY_RELATION,
    ];

    /**
     * @param string[] $classes
     * @param string[] $assetTypes
     * @param string[] $documentTypes
     */
    public function __construct(
        #[Property(
            description: 'Field type of the relation',
            type: 'string',
            enum: self::SUPPORTED_TYPES,
            example: self::TYPE_MANY_TO_MANY_RELATION
        )]
        private string $fieldtype = self::TYPE_MANY_TO_MANY_RELATION,
        #[Property(description: 'Whether objects are allowed', type: 'boolean', example: true)]
        private bool $objectsAllowed = false,
        #[Property(
            description: 'Names of the allowed classes',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['Product']
        )]
        private array $classes = [],
        #[Property(description: 'Whether assets are allowed', type: 'boolean', example: true)]
        private bool $assetsAllowed = false,
        #[Property(
            description: 'Allowed asset types',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['image']
        )]
        private array $assetTypes = [],
        #[Property(description: 'Whether documents are allowed', type: 'boolean', example: false)]
        private bool $documentsAllowed = false,
        #[Property(
            description: 'Allowed document types',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['page']
        )]
        private array $documentTypes = [],
    ) {
    }

    public function getFieldtype(): string
    {
        return $this->fieldtype;
    }

    public function isSupportedFieldtype(): bool
    {
        return in_array($this->fieldtype, self::SUPPORTED_TYPES, true);
    }

    public function isObjectsAllowed(): bool
    {
        return $this->objectsAllowed;
    }

    /**
     * @return string[]
     */
    public function getClasses(): array
    {
        return $this->filterNames($this->classes);
    }

    public function isAssetsAllowed(): bool
    {
        return $this->assetsAllowed;
    }

    /**
     * @return string[]
     */
    public function getAssetTypes(): array
    {
        return $this->filterNames($this->assetTypes);
    }

    public function isDocumentsAllowed(): bool
    {
        return $this->documentsAllowed;
    }

    /**
     * @return string[]
     */
    public function getDocumentTypes(): array
    {
        return $this->filterNames($this->documentTypes);
    }

    /**
     * @return string[]
     */
    private function filterNames(array $names): array
    {
        $filtered = [];
        foreach ($names as $name) {
            if (is_string($name) && $name !== '' && !in_array($name, $filtered, true)) {
                $filtered[] = $name;
            }
        }

        return $filtered;
    }
}
