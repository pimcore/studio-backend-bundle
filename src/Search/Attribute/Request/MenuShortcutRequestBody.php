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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleBoolean;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleString;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class MenuShortcutRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['createMenuShortcut'],
                properties: [
                    new SingleBoolean('createMenuShortcut'),
                    new SingleString('menuShortcutGroup'),
                ],
                type: 'object',
            ),
        );
    }
}
