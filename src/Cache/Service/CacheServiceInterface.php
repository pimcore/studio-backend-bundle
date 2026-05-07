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

namespace Pimcore\Bundle\StudioBackendBundle\Cache\Service;

use Pimcore\Bundle\StudioBackendBundle\Cache\MappedParameter\ClearCacheParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;

/**
 * @internal
 */
interface CacheServiceInterface
{
    /**
     * @throws EnvironmentException
     */
    public function clearCache(ClearCacheParameters $parameters): void;

    /**
     * @throws EnvironmentException
     */
    public function clearOutputCache(): void;

    /**
     * @throws EnvironmentException
     */
    public function clearTemporaryFiles(): void;
}
