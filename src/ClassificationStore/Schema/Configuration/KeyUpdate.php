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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
#[Schema(
    schema: 'ClassificationStoreConfigurationKeyUpdate',
    title: 'Classification Store Configuration Key Update',
    required: ['name', 'title', 'description', 'type', 'definition'],
    type: 'object'
)]
final readonly class KeyUpdate
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Property(description: 'Name of the key', type: 'string', example: 'My Key')]
        private string $name,
        #[Property(description: 'Title of the key', type: 'string', example: 'My Key Title')]
        private ?string $title = null,
        #[Property(description: 'Description of the key', type: 'string', example: 'Key description')]
        private ?string $description = null,
        #[Property(
            description: 'Data type of the key (e.g. input, textarea, select)',
            type: 'string',
            example: 'input'
        )]
        private ?string $type = null,
        #[Property(
            description: 'Values for object brick definition metadata',
            type: 'object',
            example: [
                'name' => 'weight',
                'datatype' => 'data',
                'fieldtype' => 'quantityValue',
                'title' => 'Weight',
                'tooltip' => '',
                'mandatory' => false,
                'index' => false,
                'noteditable' => false,
                'invisible' => false,
                'visibleGridView' => false,
                'visibleSearch' => false,
                'style' => '',
                'width' => '',
                'unitWidth' => '',
                'defaultValue' => null,
                'defaultUnit' => null,
                'defaultValueGenerator' => '',
                'validUnits' => [
                    'kg',
                ],
                'autoConvert' => false,
                'decimalSize' => null,
                'decimalPrecision' => null,
                'integer' => false,
                'unsigned' => false,
                'minValue' => null,
                'maxValue' => null,
                'displayfield-1596-inputEl' => 'The width of this component.',
            ]
        )]
        private ?array $definition = null,
    ) {
        if (empty($this->name)) {
            throw new InvalidArgumentException('Name cannot be empty.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getDefinition(): ?array
    {
        return $this->definition;
    }
}
