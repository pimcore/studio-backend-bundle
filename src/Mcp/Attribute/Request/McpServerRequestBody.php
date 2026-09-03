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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServerAccessGrant;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleBoolean;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleString;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class McpServerRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['name', 'urlSlug'],
                properties: [
                    new SingleString('name'),
                    new SingleString('urlSlug'),
                    new SingleString('description'),
                    new Property(
                        property: 'tools',
                        type: 'array',
                        items: new Items(type: 'string'),
                    ),
                    new SingleBoolean('enabled'),
                    new SingleBoolean('shareGlobal'),
                    new Property(
                        property: 'sharedUsers',
                        type: 'array',
                        items: new Items(ref: McpServerAccessGrant::class),
                    ),
                    new Property(
                        property: 'sharedRoles',
                        type: 'array',
                        items: new Items(ref: McpServerAccessGrant::class),
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
