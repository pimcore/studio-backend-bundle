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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Provider\OwnershipProviderInterface;

/**
 * @internal
 */
interface ProviderLoaderInterface
{
    public const string OWNERSHIP_PROVIDER_TAG = 'pimcore.studio_backend.ownership_provider';

    /**
     * @return array<string, OwnershipProviderInterface>
     */
    public function getProviders(): array;

    /**
     * @throws NotFoundException
     */
    public function resolve(string $type): OwnershipProviderInterface;
}
