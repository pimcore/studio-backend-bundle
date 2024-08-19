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

namespace Pimcore\Bundle\StudioBackendBundle\User\Attributes\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Property\SingleString;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Property\UpdateIntegerProperty;

#[Attribute(Attribute::TARGET_METHOD)]
final class CreateRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['parentId', 'name', ],
                properties: [
                    new UpdateIntegerProperty('parentId'),
                    new SingleString('name'),
                ],
                type: 'object'
            ),
        );
    }
}
