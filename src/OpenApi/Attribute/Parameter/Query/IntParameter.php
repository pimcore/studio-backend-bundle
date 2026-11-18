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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class IntParameter extends QueryParameter
{
    public function __construct(
        string $name,
        string $description,
        bool $required = true,
        int $example = 42,
        ?int $minimum = null,
    ) {
        parent::__construct(
            name: $name,
            description: $description,
            in: 'query',
            required: $required,
            // `null` is swagger-php's own "not set": the attribute maps it back to
            // Generator::UNDEFINED, so an unconstrained parameter emits no `minimum` key.
            schema: new Schema(type: 'integer', minimum: $minimum, example: $example),
        );
    }
}
