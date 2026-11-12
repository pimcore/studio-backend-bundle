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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class SingleParameterRequestBody extends RequestBody
{
    /**
     * @param bool $parameterRequired marks the single property as required in the schema,
     *                                so an empty object no longer validates. Opt-in: it
     *                                changes the published contract, and a body whose one
     *                                parameter is genuinely optional stays as it was.
     */
    public function __construct(
        string $parameterName,
        mixed $example,
        string $type = 'string',
        bool $parameterRequired = false,
    ) {
        parent::__construct(
            required: true,
            content: new JsonContent(
                properties: [
                    new Property(
                        $parameterName,
                        type: $type,
                        example: $example,
                    ),
                ],
                type: 'object',
                required: $parameterRequired ? [$parameterName] : null,
            ),
        );
    }
}
