<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property\CustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\PatchCustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateIntegerProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateStringProperty;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PatchAssetRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['data'],
                properties: [
                    new Property(
                        property: 'data',
                        type: 'array',
                        items: new Items(
                            required: ['id'],
                            properties: [
                                new Property(property: 'id', description: 'Asset ID', type: 'integer', example: 83),
                                new UpdateIntegerProperty('parentId'),
                                new UpdateStringProperty('key'),
                                new UpdateStringProperty('locked'),
                                new CustomMetadata(PatchCustomMetadata::class),
                            ],
                            type: 'object',
                        ),
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
