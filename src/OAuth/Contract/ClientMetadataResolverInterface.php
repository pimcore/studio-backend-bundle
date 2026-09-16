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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Contract;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ClientMetadata;

/**
 * Resolves a URL-form client_id into client metadata by fetching the Client ID
 * Metadata Document it points at. Returns null when CIMD is disabled, the URL
 * is not acceptable, or the document cannot be fetched/validated.
 *
 * @internal
 */
interface ClientMetadataResolverInterface
{
    public function resolve(string $clientId): ?ClientMetadata;
}
