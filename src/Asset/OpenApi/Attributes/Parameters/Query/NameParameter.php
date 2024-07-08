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
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Thumbnails;

#[Attribute(Attribute::TARGET_METHOD)]
final class NameParameter extends QueryParameter
{
    public function __construct(
        $name = 'thumbnailName',
        $description = 'Find asset by matching thumbnail name.',
        $example = Thumbnails::DEFAULT_THUMBNAIL_ID->value,
    ) {
        parent::__construct(
            name: $name,
            description: $description,
            in: 'query',
            required: true,
            schema: new Schema(
                type: 'string',
                example: $example
            ),
        );
    }
}
