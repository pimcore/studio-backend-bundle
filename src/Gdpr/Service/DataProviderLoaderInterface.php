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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface;

/**
 * @internal
 */
interface DataProviderLoaderInterface
{
    public const string DATA_PROVIDER_TAG = 'pimcore.studio_backend.gdpr_data_provider';

    /**
     * @return array<string, DataProviderInterface>
     */
    public function getDataProviders(): array;

    /**
     * @throws NotFoundException
     */
    public function resolve(string $key): DataProviderInterface;
}
