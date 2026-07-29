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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Exception\InvalidMcpToolArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\ObjectParameterNormalizer;

/**
 * @internal
 */
final class ObjectParameterNormalizerTest extends Unit
{
    /**
     * All three encodings of "nothing" have to survive, and stay distinguishable: a tool may treat
     * "not supplied" differently from "supplied but empty", so neither is coerced into the other.
     */
    public function testEveryEncodingOfNothingIsReturnedUnchanged(): void
    {
        $this->assertNull(ObjectParameterNormalizer::normalize(null, 'filter'));
        $this->assertSame([], ObjectParameterNormalizer::normalize([], 'filter'));
    }

    public function testAnObjectIsReturnedUnchanged(): void
    {
        $value = ['colour' => 'red', 'size' => 2];

        $this->assertSame($value, ObjectParameterNormalizer::normalize($value, 'filter'));
    }

    /**
     * The hole that ToolInputSchemaNormalizer's widening opens: the schema has to admit `array` so
     * that `{}` is accepted, which also lets a populated list through.
     */
    public function testAPopulatedListIsRejected(): void
    {
        $this->expectException(InvalidMcpToolArgumentException::class);

        ObjectParameterNormalizer::normalize(['a', 'b'], 'filter');
    }

    /**
     * The message is the reason this throws a forwardable type: it names the caller's own parameter
     * and says how to send nothing, so the agent can correct itself.
     */
    public function testRejectionNamesTheParameterAndHowToSendNothing(): void
    {
        try {
            ObjectParameterNormalizer::normalize([['id' => 1]], 'orderBy');
            $this->fail('Expected a populated list to be rejected.');
        } catch (InvalidMcpToolArgumentException $e) {
            $this->assertStringContainsString('"orderBy" parameter', $e->getMessage());
            $this->assertStringContainsString('Omit it, or pass null or {}', $e->getMessage());
        }
    }

    /**
     * A map with sequential integer keys is a list as far as PHP is concerned, and a map with any
     * string key is not — the distinction the guard rests on.
     */
    public function testAMapWithNonSequentialKeysIsNotAList(): void
    {
        $value = [2 => 'b', 0 => 'a'];

        $this->assertSame($value, ObjectParameterNormalizer::normalize($value, 'filter'));
    }
}
