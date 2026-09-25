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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Tool;

use Pimcore\Bundle\StudioBackendBundle\Mcp\Exception\InvalidMcpToolArgumentException;
use function array_is_list;
use function sprintf;

/**
 * Guards an object-shaped MCP tool parameter.
 *
 * Such a parameter accepts an object or nothing, but "nothing" has three encodings a client may
 * use: the parameter is omitted, it is null, or it is an empty object. The declared schema has to
 * admit all three, which means it also has to admit `array` — by the time the tool runs the
 * transport has decoded the payload associatively, so an empty object is indistinguishable from an
 * empty list. Admitting `array` would also let a populated list through, so that case is rejected
 * here instead.
 *
 * This is the second half of one mechanism: {@see ToolInputSchemaNormalizer} widens the declared
 * type so `{}` is accepted, and this closes the hole that widening opens. Use them together.
 *
 * The value is returned unchanged rather than coerced: a tool may distinguish "not supplied" (null)
 * from "supplied but empty", and collapsing the two would change its behaviour.
 *
 * ## Known limitation: object keys must not be sequential integers from zero
 *
 * `{"0":"a","1":"b"}` is rejected, even though the advertised schema says `object`. That is not a
 * choice this class can make differently. `Mcp\JsonRpc\MessageFactory` decodes the payload with
 * `json_decode($input, true)` before any tool is reached, and PHP casts numeric string keys to
 * integers, so `{"0":"a","1":"b"}` and `["a","b"]` decode to *identical* values — `===` returns
 * true. No inspection downstream can tell them apart, so a guard that rejects populated lists
 * necessarily rejects that one object shape too.
 *
 * In practice this costs nothing: the parameters worth guarding are maps keyed by names the caller
 * chose — editable names, field names, workflow options, tool arguments — and `"0"` is not one of
 * them. A map with any non-integer key, or with integer keys that are not sequential from zero
 * (`{"0":"a","2":"b"}`), passes normally. Do not use this guard for a parameter whose keys are
 * genuinely arbitrary integers; give that parameter an `array` schema and validate it in the tool.
 */
final class ObjectParameterNormalizer
{
    private function __construct()
    {
        // static-only utility class, must not be instantiated
    }

    /**
     * @param array<array-key, mixed>|null $value
     *
     * @return array<array-key, mixed>|null the value unchanged, once its shape is known to be valid
     *
     * @throws InvalidMcpToolArgumentException when a populated list is passed instead of an object
     */
    public static function normalize(?array $value, string $parameterName): ?array
    {
        if ($value === null || $value === []) {
            return $value;
        }

        if (array_is_list($value)) {
            // Client-safe by construction: the message names the caller's own parameter and says
            // how to send nothing. This is the shipped example of a correct throw of that type.
            throw new InvalidMcpToolArgumentException(sprintf(
                'The "%s" parameter must be an object of field/value pairs, not a list. '
                . 'Omit it, or pass null or {}, to send no %s.',
                $parameterName,
                $parameterName
            ));
        }

        return $value;
    }
}
