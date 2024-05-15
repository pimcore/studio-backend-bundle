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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Setting\Response\SettingsStoreContent;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class SettingsStoreResponse extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            type: 'array',
            items: new Items(ref: SettingsStoreContent::class),
        );
    }
}
