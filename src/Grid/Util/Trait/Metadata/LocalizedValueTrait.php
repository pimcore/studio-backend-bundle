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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\Metadata;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;

trait LocalizedValueTrait
{
    /**
     * @throws InvalidArgumentException
     */
    private function getLocalizedValue(Column $column, StudioElementInterface $element): mixed
    {
        if (!$element instanceof Asset) {
            throw new InvalidArgumentException('Element must be an instance of Asset');
        }

        return $this->getMetadataByNameAndLanguage($element->getMetadata(), $column->getKey(), $column->getLocale());
    }

    private function getMetadataByNameAndLanguage(array $metadata, string $name, ?string $language): mixed
    {
        foreach ($metadata as $assetMetadata) {
            if ($assetMetadata->getLanguage() === $language && $assetMetadata->getName() === $name) {
                return $assetMetadata->getData();
            }
        }

        return null;
    }
}
