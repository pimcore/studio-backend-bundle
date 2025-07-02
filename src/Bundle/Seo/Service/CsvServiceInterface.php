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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service;

use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RedirectImportStats;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
interface CsvServiceInterface
{
    /**
     * @throws EnvironmentException
     */
    public function exportRedirects(): Response;

    /**
     * @throws EnvironmentException
     */
    public function importRedirects(string $filePath): RedirectImportStats;
}
