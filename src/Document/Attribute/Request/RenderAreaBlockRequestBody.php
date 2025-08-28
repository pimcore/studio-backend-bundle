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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateBooleanProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateIntegerProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateObjectProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateStringProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Attribute\Property\UpdateElementProperties;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentFieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class RenderAreaBlockRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['name', 'realName', 'index', 'blockStateStack', 'areablockConfig', 'areablockData'],
                properties: [
                    new UpdateStringProperty('name'),
                    new UpdateStringProperty('realName'),
                    new UpdateIntegerProperty('index', 0),
                    new UpdateObjectProperty('blockStateStack', '[{"blocks": [], "indexes": []}]'),
                    new UpdateObjectProperty('areaBlockConfig', '[]'),
                    new UpdateObjectProperty('areaBlockData', '[]'),
                ],
                type: 'object',
            ),
        );
    }
}
