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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\Data;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;

/**
 * @internal
 */
interface CustomMetadataServiceInterface
{
    public const DEFAULT_METADATA = ['title', 'alt', 'copyright'];

    /**
     * @return array<int, CustomMetadata>
     *
     * @throws AccessDeniedException
     *
     */
    public function getCustomMetadata(int $id): array;
}
