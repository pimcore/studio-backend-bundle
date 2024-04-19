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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioApiBundle\Service\Filter\FilterServiceInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ElementTypeParameter extends QueryParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'type',
            description: 'Element type.',
            in: 'query',
            required: true,
            schema: new Schema(type: 'string', enum: [
                FilterServiceInterface::TYPE_ASSET,
                FilterServiceInterface::TYPE_DATA_OBJECT,
                FilterServiceInterface::TYPE_DOCUMENT,
            ], example: 'asset'),
        );
    }
}
