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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataDenormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\MetaDataAdapterInterface;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * @internal
 */
interface DataAdapterServiceInterface
{
    public function getStudioAdaptersMapping(): array;

    /**
     * @throws InvalidArgumentException
     */
    public function getMetadataAdapter(string $type): ?MetadataAdapterInterface;

    /**
     * @throws InvalidArgumentException
     */
    public function getNormalizerAdapter(string $type): null|DataNormalizerInterface|NormalizerInterface;

    /**
     * @throws InvalidArgumentException
     */
    public function getDenormalizerAdapter(string $type): null|DataDenormalizerInterface|NormalizerInterface;
}
