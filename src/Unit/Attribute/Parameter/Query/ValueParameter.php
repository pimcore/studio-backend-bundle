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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Attribute\Parameter\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_METHOD)]
final class ValueParameter extends QueryParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'value',
            description: 'Value to convert.',
            in: 'query',
            required: true,
            schema: new Schema(
                anyOf: [
                    new Schema(type: 'integer', format: 'int32'),
                    new Schema(type: 'number', format: 'float'),
                ],
            ),
            example: 5
        );
    }
}
