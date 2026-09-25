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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Tool;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\ToolInputSchemaNormalizer;

/**
 * @internal
 */
final class ToolInputSchemaNormalizerTest extends Unit
{
    /**
     * The defect: a #[Schema] attribute pins the type to a single string, overriding the nullable
     * type inferred from the signature, while the SDK still emits `default: null`. A client that
     * honours the advertised default is then rejected by the schema that advertised it.
     */
    public function testOptionalParameterAdvertisingANullDefaultAcceptsNull(): void
    {
        $normalized = ToolInputSchemaNormalizer::normalize([
            'properties' => ['query' => ['type' => 'string', 'default' => null]],
        ]);

        $this->assertSame(['string', 'null'], $normalized['properties']['query']['type']);
    }

    /**
     * An empty object arrives as an empty PHP array once the transport has decoded the payload
     * associatively, so rejecting `array` rejects `{}`.
     */
    public function testObjectParameterAlsoAcceptsArray(): void
    {
        $normalized = ToolInputSchemaNormalizer::normalize([
            'properties' => ['filter' => ['type' => 'object']],
        ]);

        $this->assertSame(['object', 'array'], $normalized['properties']['filter']['type']);
    }

    public function testBothWideningsCombine(): void
    {
        $normalized = ToolInputSchemaNormalizer::normalize([
            'properties' => ['filter' => ['type' => 'object', 'default' => null]],
        ]);

        $this->assertSame(['object', 'array', 'null'], $normalized['properties']['filter']['type']);
    }

    /**
     * A required parameter has no default to contradict, so null is not added — the schema should
     * keep rejecting null for something the caller must supply.
     */
    public function testRequiredParameterDoesNotGainNull(): void
    {
        $normalized = ToolInputSchemaNormalizer::normalize([
            'properties' => ['id' => ['type' => 'integer', 'default' => null]],
            'required' => ['id'],
        ]);

        $this->assertSame('integer', $normalized['properties']['id']['type']);
    }

    /**
     * A non-null default says nothing about accepting null.
     */
    public function testNonNullDefaultDoesNotWiden(): void
    {
        $normalized = ToolInputSchemaNormalizer::normalize([
            'properties' => ['pageSize' => ['type' => 'integer', 'default' => 20]],
        ]);

        $this->assertSame('integer', $normalized['properties']['pageSize']['type']);
    }

    public function testAlreadyWideTypesAreNotDuplicated(): void
    {
        $normalized = ToolInputSchemaNormalizer::normalize([
            'properties' => ['filter' => ['type' => ['object', 'array', 'null'], 'default' => null]],
        ]);

        $this->assertSame(['object', 'array', 'null'], $normalized['properties']['filter']['type']);
    }

    public function testSchemaWithoutPropertiesIsReturnedUnchanged(): void
    {
        $schema = ['type' => 'object'];

        $this->assertSame($schema, ToolInputSchemaNormalizer::normalize($schema));
    }

    /**
     * A property with no declared type is left alone rather than guessed at.
     */
    public function testPropertyWithoutATypeIsUntouched(): void
    {
        $normalized = ToolInputSchemaNormalizer::normalize([
            'properties' => ['anything' => ['description' => 'no type declared', 'default' => null]],
        ]);

        $this->assertArrayNotHasKey('type', $normalized['properties']['anything']);
    }
}
