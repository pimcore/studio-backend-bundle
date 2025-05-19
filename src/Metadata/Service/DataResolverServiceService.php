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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Service;

use Pimcore\Model\UserInterface;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final readonly class DataResolverServiceService implements DataResolverServiceInterface
{
    public function __construct(private DataAdapterServiceInterface $dataAdapterService)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeData(array $customMetadata): mixed
    {
        $adapter = $this->dataAdapterService->getNormalizerAdapter($customMetadata['type']);
        if ($adapter === null) {
            return $customMetadata['data'];
        }

        if ($adapter instanceof NormalizerInterface) {
            return $adapter->normalize($customMetadata['data']);
        }

        return $adapter->normalize($customMetadata['data'], $customMetadata['type']);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalizeData(
        array $customMetadata,
        UserInterface $user,
        array $existingMetadata = [],
        bool $isPatch = false
    ): mixed {
        $adapter = $this->dataAdapterService->getDenormalizerAdapter($customMetadata['type']);
        $data = $customMetadata['data'];
        if ($adapter === null) {
            return $data;
        }

        if ($adapter instanceof NormalizerInterface) {
            return $adapter->denormalize($data);
        }

        return $adapter->denormalize($customMetadata, $user, $existingMetadata, $isPatch);
    }
}
