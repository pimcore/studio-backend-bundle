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
final class PageSizeParameter extends QueryParameter
{
    public function __construct(int $defaultSize = 10, ?int $maxSize = null)
    {
        parent::__construct(
            name: 'pageSize',
            description: 'Number of items per page',
            in: 'query',
            required: true,
            schema: new Schema(type: 'integer', minimum: 1, maximum: $maxSize, example: $defaultSize),
        );
    }
}
