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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Service;

use Pimcore\Bundle\StudioBackendBundle\Gdpr\MappedParameter\GdprStructuredSearchRequest;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataProvider;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprSearchResultCollection;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface GdprManagerServiceInterface
{
    /**
     * Returns a sorted collection of all available GDPR data providers.
     *
     * @return Collection<GdprDataProvider>
     */
    public function getAvailableProviders(): Collection;

    /**
     * Searches for data in the specified providers.
     *
     * @throws ForbiddenException
     */
    public function search(GdprStructuredSearchRequest $request): GdprSearchResultCollection;

    /**
     * @throws ForbiddenException
     */
    public function startBackgroundExport(GdprStructuredSearchRequest $request): GdprExportJobCollection;

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function getExportFile(int $jobRunId): StreamedResponse;
}
