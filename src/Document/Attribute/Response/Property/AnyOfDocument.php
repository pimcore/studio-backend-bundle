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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Attribute\Response\Property;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\Schemas;

/**
 * @internal
 */
final class AnyOfDocument extends Property
{
    public function __construct()
    {
        parent::__construct(
            'items',
            title: 'items',
            type: 'array',
            items: new Items(
                anyOf: array_map(static function ($class) {
                    return new Schema(ref: $class);
                }, Schemas::DOCUMENTS)
            )
        );
    }
}
