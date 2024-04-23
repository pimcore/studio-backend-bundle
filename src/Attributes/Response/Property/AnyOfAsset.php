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

namespace Pimcore\Bundle\StudioApiBundle\Attributes\Response\Property;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioApiBundle\Response\Schemas;

/**
 * @internal
 */
final class AnyOfAsset extends Property
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
                }, Schemas::ASSETS)
            )
        );
    }
}
