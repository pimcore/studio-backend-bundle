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

use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataDenormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\MetaDataAdapterInterface;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Model\Asset\Metadata\Loader\DataLoader;
use Pimcore\Normalizer\NormalizerInterface;
use function in_array;

/**
 * @internal
 */
final readonly class DataAdapterService implements DataAdapterServiceInterface
{
    public function __construct(
        private array $studioAdapters,
        private DataAdapterLoaderInterface $dataAdapterLoader,
        private DataLoader $loader,
    ) {
    }

    public function getStudioAdaptersMapping(): array
    {
        return $this->studioAdapters;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataAdapter(string $type): ?MetadataAdapterInterface
    {
        $studioAdapters = $this->getStudioAdaptersMapping();
        foreach ($studioAdapters as $adapter => $fieldTypes) {
            if (in_array($type, $fieldTypes, true)) {
                return $this->dataAdapterLoader->loadAdapter($adapter);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizerAdapter(string $type): null|DataNormalizerInterface|NormalizerInterface
    {
        $studioAdapter = $this->getMetadataAdapter($type);
        if ($studioAdapter instanceof DataNormalizerInterface) {
            return $studioAdapter;
        }

        return $this->getCoreNormalizerAdapter($type);
    }

    /**
     * {@inheritdoc}
     */
    public function getDenormalizerAdapter(string $type): null|DataDenormalizerInterface|NormalizerInterface
    {
        $studioAdapter = $this->getMetadataAdapter($type);
        if ($studioAdapter instanceof DataDenormalizerInterface) {
            return $studioAdapter;
        }

        return $this->getCoreNormalizerAdapter($type);
    }

    private function getCoreNormalizerAdapter(string $type): ?NormalizerInterface
    {
        try {
            $coreAdapter = $this->loader->build($type);
            if ($coreAdapter instanceof NormalizerInterface) {
                return $coreAdapter;
            }
        } catch (UnsupportedException) {
            return null;
        }

        return null;
    }
}
