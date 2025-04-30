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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Data\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\MetaDataAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(AdapterLoader::METADATA_ADAPTER_TAG->value)]
final readonly class BooleanAdapter implements MetaDataAdapterInterface, DataNormalizerInterface
{
    public function normalize(mixed $value, string $type): ?bool
    {
        if ($value === null) {
            return null;
        }

        return (bool)$value;
    }
}
