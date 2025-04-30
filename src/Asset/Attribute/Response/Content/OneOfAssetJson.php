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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Response\Content;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\Schemas;

/**
 * @internal
 */
final class OneOfAssetJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            type: 'object',
            oneOf: array_map(static function ($class) {
                return new Schema(ref: $class);
            }, Schemas::ASSETS),
        );
    }
}
