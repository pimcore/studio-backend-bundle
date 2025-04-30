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
use OpenApi\Attributes\QueryParameter as OpenApiQueryParameter;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_METHOD)]
final class FilterParameter extends OpenApiQueryParameter
{
    public function __construct(string $filterFor = 'properties', ?string $example = null)
    {
        parent::__construct(
            name: 'filter',
            description: 'Filter for ' . $filterFor,
            in: 'query',
            required: false,
            schema: new Schema(type: 'string', example: $example ?? $filterFor),
        );
    }
}
