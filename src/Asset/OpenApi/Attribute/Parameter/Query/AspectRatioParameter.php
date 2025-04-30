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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_METHOD)]
final class AspectRatioParameter extends QueryParameter
{
    public function __construct(string $description = 'Aspect ratio', mixed $defaultValue = null)
    {
        parent::__construct(
            name: 'aspectRatio',
            description: $description,
            in: 'query',
            schema: new Schema(type: 'boolean', example: $defaultValue),
        );
    }
}
