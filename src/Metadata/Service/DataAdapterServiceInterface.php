<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
