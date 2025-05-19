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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Response\Schema\PatchError;

/**
 * @internal
 */
final class PatchErrorJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            type: 'array',
            items: new Items(ref: PatchError::class),
        );
    }
}
