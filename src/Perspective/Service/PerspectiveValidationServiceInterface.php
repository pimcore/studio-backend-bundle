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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SavePerspectiveConfig;

/**
 * @internal
 */
interface PerspectiveValidationServiceInterface
{
    /**
     * @throws InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function validateWidgets(SavePerspectiveConfig $perspectiveConfig): void;

    /**
     * @throws NotFoundException
     */
    public function validateExpandedWidgets(SavePerspectiveConfig $perspectiveConfig): void;

    public function getValidContextPermissions(array $perspectivePermissions): array;
}
