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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * An assignable MCP tool, as offered to the server editor.
 *
 * @internal
 */
#[Schema(
    schema: 'McpToolItem',
    title: 'MCP Tool',
    required: ['name', 'title', 'description', 'requiredScope', 'readOnly', 'destructive'],
    type: 'object'
)]
final class McpToolItem implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Tool id (used in a server\'s tool list)', type: 'string', example: 'get_car_info')]
        private readonly string $name,
        #[Property(description: 'Human-facing title', type: 'string', example: 'Get Car Info')]
        private readonly string $title,
        #[Property(description: 'Description', type: 'string', example: 'Returns short info about a data object')]
        private readonly string $description,
        #[Property(description: 'OAuth scope the tool requires', type: 'string', example: 'mcp:read')]
        private readonly string $requiredScope,
        #[Property(description: 'Read-only hint', type: 'boolean', example: true)]
        private readonly bool $readOnly,
        #[Property(description: 'Destructive hint', type: 'boolean', example: false)]
        private readonly bool $destructive,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRequiredScope(): string
    {
        return $this->requiredScope;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function isDestructive(): bool
    {
        return $this->destructive;
    }
}
