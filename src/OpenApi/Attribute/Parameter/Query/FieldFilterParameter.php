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
final class FieldFilterParameter extends OpenApiQueryParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'fieldFilters',
            description: 'Filter for specific fields, will be json decoded to an array. e.g.
            [{"operator":"like","value":"John","field":"name","type":"string"}]',
            in: 'query',
            required: false,
            schema: new Schema(
                type: 'string',
                example: '[{"operator":"like","value":"John","field":"name", "type":"string"}]',
            ),
        );
    }
}
