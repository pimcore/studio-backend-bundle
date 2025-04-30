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
