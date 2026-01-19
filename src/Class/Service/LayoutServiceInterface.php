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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\TextLayoutPreviewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\LayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface LayoutServiceInterface
{
    /**
     * @return LayoutCompact[]
     */
    public function getAllLayoutsCollection(): array;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getTextLayoutPreview(TextLayoutPreviewParameters $parameters): string;
}
