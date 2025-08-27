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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Translation\MappedParameter\CsvSettingsParameter;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\DeltaItem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
interface ImportServiceInterface
{
    /**
     * @throws ForbiddenException|EnvironmentException|UserNotFoundException
     *
     * @return DeltaItem[]
     */
    public function import(UploadedFile $file, string $domain, CsvSettingsParameter $parameter): array;
}
