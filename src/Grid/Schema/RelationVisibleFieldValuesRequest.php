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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use function count;
use function is_array;
use function is_numeric;
use function sprintf;

/**
 * The rows of a many-to-many relation or advanced many-to-many relation whose visible field values are
 * requested. The values are loaded per page of rows, never for the whole relation at once.
 */
#[Schema(
    schema: 'RelationVisibleFieldValuesRequest',
    title: 'Relation Visible Field Values Request',
    required: ['classId', 'relationField', 'elements'],
    type: 'object'
)]
final readonly class RelationVisibleFieldValuesRequest
{
    public const int MAX_ELEMENTS = 200;

    private const array ELEMENT_TYPE_ALIASES = [
        ElementTypes::TYPE_OBJECT => ElementTypes::TYPE_OBJECT,
        ElementTypes::TYPE_DATA_OBJECT => ElementTypes::TYPE_OBJECT,
        ElementTypes::TYPE_ASSET => ElementTypes::TYPE_ASSET,
        ElementTypes::TYPE_DOCUMENT => ElementTypes::TYPE_DOCUMENT,
    ];

    public function __construct(
        #[Property(description: 'Class id of the object holding the relation', type: 'string', example: 'CAR')]
        private string $classId,
        #[Property(
            description: 'Relation field as dot notation, e.g. "myBlock.mySubRelationField"',
            type: 'string',
            example: 'myRelationField'
        )]
        private string $relationField,
        #[Property(
            description: 'Language of localized fields and language-specific metadata',
            type: 'string',
            example: 'en',
            nullable: true
        )]
        private ?string $locale = null,
        #[Property(
            description: 'The related elements of the rows to load the values for (at most 200)',
            type: 'array',
            items: new Items(
                required: ['type', 'id'],
                properties: [
                    new Property(property: 'type', type: 'string', example: 'asset'),
                    new Property(property: 'id', type: 'integer', example: 83),
                ],
                type: 'object'
            ),
        )]
        private array $elements = [],
    ) {
    }

    public function getClassId(): string
    {
        return $this->classId;
    }

    public function getRelationField(): string
    {
        return $this->relationField;
    }

    public function getLocale(): ?string
    {
        return $this->locale === '' ? null : $this->locale;
    }

    /**
     * The requested elements as (type, id) pairs, element types normalized to the core names
     * (object, asset, document), duplicates removed.
     *
     * @return array<int, array{type: string, id: int}>
     *
     * @throws InvalidArgumentException
     */
    public function getElements(): array
    {
        if (count($this->elements) > self::MAX_ELEMENTS) {
            throw new InvalidArgumentException(
                sprintf('At most %d elements can be requested at once', self::MAX_ELEMENTS)
            );
        }

        $elements = [];
        foreach ($this->elements as $element) {
            $type = is_array($element) ? (self::ELEMENT_TYPE_ALIASES[$element['type'] ?? ''] ?? null) : null;
            $id = is_array($element) ? ($element['id'] ?? null) : null;

            if ($type === null || !is_numeric($id)) {
                throw new InvalidArgumentException('Each element needs a valid type and id');
            }

            $elements[$type . ':' . (int) $id] = ['type' => $type, 'id' => (int) $id];
        }

        return array_values($elements);
    }
}
