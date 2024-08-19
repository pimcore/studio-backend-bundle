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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attributes\Parameters\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\ResizeModes;

#[Attribute(Attribute::TARGET_METHOD)]
final class ResizeModeParameter extends QueryParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'resizeMode',
            description: 'Resize mode of downloaded image.',
            in: 'query',
            required: true,
            schema: new Schema(
                type: 'string',
                enum: [
                    ResizeModes::RESIZE,
                    ResizeModes::SCALE_BY_WIDTH,
                    ResizeModes::SCALE_BY_HEIGHT,
                ],
                example: ResizeModes::SCALE_BY_WIDTH
            ),
        );
    }
}
