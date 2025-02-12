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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\MappedParameter\MetadataParameters;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\CustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\PredefinedMetadata;

/**
 * @internal
 */
interface MetadataServiceInterface
{
    public const DEFAULT_METADATA = ['title', 'alt', 'copyright'];

    /**
     * @return array<int, CustomMetadata>
     *
     * @throws ForbiddenException|NotFoundException
     *
     */
    public function getCustomMetadata(int $id): array;

    /**
     * @return array<int, PredefinedMetadata>
     */
    public function getPredefinedMetadata(MetadataParameters $parameters): array;
}
