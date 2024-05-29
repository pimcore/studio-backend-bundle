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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Property\UpdateAssetImage;
use Pimcore\Bundle\StudioBackendBundle\Property\Attributes\Property\UpdateElementProperties;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class UpdateAssetRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                properties: [
                    new Property('data',
                        properties: [
                            new UpdateElementProperties(),
                            new UpdateAssetImage()
                        ],
                        type: 'object',
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
