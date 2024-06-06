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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Response\Content;

use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
final class AssetMediaType extends MediaType
{
    public function __construct($mimeType = 'application/*')
    {
        parent::__construct(
            mediaType: $mimeType,
            schema: new Schema(
                type: 'string',
                format: 'binary'
            )
        );
    }
}
