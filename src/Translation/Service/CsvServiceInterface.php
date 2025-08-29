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
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CsvSettings;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
interface CsvServiceInterface
{
    /**
     * @throws ForbiddenException|EnvironmentException
     */
    public function export(string $domain, CollectionFilterParameter $parameter): Response;

    public function determineCsvDialect(string $sample): CsvSettings;
}
