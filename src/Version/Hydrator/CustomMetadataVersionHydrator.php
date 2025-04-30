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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\DataResolverServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\CustomMetadataVersion;

/**
 * @internal
 */
final readonly class CustomMetadataVersionHydrator implements CustomMetadataVersionHydratorInterface
{
    public function __construct(private DataResolverServiceInterface $dataResolverService)
    {
    }

    /** @return array<int, CustomMetadataVersion> */
    public function hydrate(array $customMetadata): array
    {
        return array_map(
            fn (array $customMetadata): CustomMetadataVersion => $this->hydrateSingle($customMetadata),
            $customMetadata
        );
    }

    private function hydrateSingle(array $customMetadata): CustomMetadataVersion
    {
        return new CustomMetadataVersion(
            $customMetadata['name'],
            $customMetadata['language'],
            $customMetadata['type'],
            $this->dataResolverService->normalizeData($customMetadata)
        );
    }
}
