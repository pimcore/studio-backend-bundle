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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Attribute\Property\FilterProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateStringProperty;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class DrillDownRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['name', 'field'],
                properties: [
                    new UpdateStringProperty('name'),
                    new UpdateStringProperty('field'),
                    new FilterProperty(),
                ],
                type: 'object',
            ),
        );
    }
}
