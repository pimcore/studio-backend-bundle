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

#[Attribute(Attribute::TARGET_METHOD)]
final class ParentIdParameter extends QueryParameter
{
    public function __construct(string $description, bool $required = false, int $minimum = 1, ?int $example = 1)
    {
        parent::__construct(
            name: 'parentId',
            description: $description,
            in: 'query',
            required: $required,
            schema: new Schema(type: 'integer', minimum: $minimum, example: $example),
        );
    }
}
