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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Attribute\Parameter\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Service\DependencyMode;

#[Attribute(Attribute::TARGET_METHOD)]
final class DependencyModeParameter extends QueryParameter
{
    public function __construct(
    ) {
        parent::__construct(
            name: 'dependencyMode',
            description: 'Dependency mode',
            in: 'query',
            required: true,
            schema: new Schema(
                type: 'string',
                enum: DependencyMode::cases(),
                example: DependencyMode::REQUIRED_BY->value
            ),
        );
    }
}
