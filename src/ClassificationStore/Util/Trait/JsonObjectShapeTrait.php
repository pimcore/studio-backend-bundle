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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait;

use function array_is_list;
use function is_array;

/**
 * Whether a decoded JSON value is the `object` a `type: 'object'` member asks for, asked the same
 * way everywhere free-form pairs cross a write boundary: the four configuration update schemas, the
 * assignment upsert and the import artifact. `is_array()` on its own is not that question - PHP
 * carries a JSON list as an array too, so `{"customMetadata": ["erp"]}` passes it and is then
 * stored under a member the contract says is an object.
 *
 * The `{}` and `[]` distinction is decided deliberately rather than left to fall out:
 * `json_decode($json, true)` hands both back as the same empty array, so an empty array has to stay
 * acceptable - it is the only representation `{}` has once decoded, and refusing it would refuse
 * the empty object the contract allows. A NON-EMPTY list is the one shape that can only have
 * arrived as a JSON array, and that is what is refused.
 *
 * The same decoding costs one legal object: `{"0": "a", "1": "b"}`, whose member names happen to be
 * the consecutive integers from zero, is a list once decoded and is refused with the array it cannot
 * be told apart from. Nothing recovers that inside the request - the decoding is done before these
 * boundaries are reached, by Symfony's serializer and by `json_decode($json, true)` in the artifact
 * parser - and a free-form pair named "0" is a far rarer thing to send than a list is to send by
 * mistake, which is the trade taken here.
 *
 * @internal
 */
trait JsonObjectShapeTrait
{
    private function isJsonObject(mixed $value): bool
    {
        return is_array($value) && ($value === [] || !array_is_list($value));
    }
}
