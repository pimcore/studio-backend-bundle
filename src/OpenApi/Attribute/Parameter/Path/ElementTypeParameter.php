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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path;

use Attribute;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

#[Attribute(Attribute::TARGET_METHOD)]
final class ElementTypeParameter extends PathParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'elementType',
            description: 'Filter elements by matching element type.',
            in: 'path',
            required: true,
            schema: new Schema(
                type: 'string',
                enum: [
                    ElementTypes::TYPE_ASSET,
                    ElementTypes::TYPE_DOCUMENT,
                    ElementTypes::TYPE_DATA_OBJECT,
                ],
                example: ElementTypes::TYPE_DATA_OBJECT,
            ),
        );
    }
}
