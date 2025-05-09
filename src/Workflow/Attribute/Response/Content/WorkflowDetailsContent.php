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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Attribute\Response\Content;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Attribute\Response\Property\WorkflowDetailsCollection;

/**
 * @internal
 */
final class WorkflowDetailsContent extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['items', 'layoutId'],
            properties: [
                new WorkflowDetailsCollection(),
                new Property(property: 'layoutId', title: 'layoutId', type: 'string'),
            ],
            type: 'object',
        );
    }
}
