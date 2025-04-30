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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Attribute\Parameter\Path;

use Attribute;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Tag\Util\Constant\BatchOperations;

#[Attribute(Attribute::TARGET_METHOD)]
final class BatchOperationParameter extends PathParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'operation',
            description: 'Execute operation based on provided type.',
            in: 'path',
            required: true,
            schema: new Schema(
                type: 'string',
                enum: [
                    BatchOperations::ASSIGN->value,
                    BatchOperations::REPLACE->value,
                ],
                example: BatchOperations::ASSIGN->value,
            ),
        );
    }
}
