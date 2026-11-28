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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidPathException;

/**
 * @internal
 */
interface OpenApiExportServiceInterface
{
    /**
     * Exports the OpenAPI spec as an array: a pure function of the scanned
     * code — deterministic, optionally translated, optionally restricted to
     * path scopes (segment-aware) with unreferenced components dropped.
     *
     * @param array|null  $scanPaths         paths to scan; null uses the configured ones
     * @param string|null $locale            translate summary/description values; null keeps the raw keys
     * @param array       $scopes            path prefixes to restrict to; empty keeps everything
     * @param bool        $applyBundleFilter false keeps the optional-bundle endpoints regardless of
     *                                       which bundles are active
     * @param bool        $sortAllKeys       canonical key order (paths/properties/responses order kept)
     *
     * @throws EnvironmentException|InvalidPathException
     */
    public function exportConfig(
        ?array $scanPaths = null,
        ?string $locale = null,
        array $scopes = [],
        bool $applyBundleFilter = true,
        bool $sortAllKeys = false,
    ): array;
}
