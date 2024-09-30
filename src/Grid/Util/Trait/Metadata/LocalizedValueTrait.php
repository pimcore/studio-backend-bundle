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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\Metadata;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementInterface;

/**
 * @internal
 */
trait LocalizedValueTrait
{
    /**
     * @throws InvalidArgumentException
     */
    private function getLocalizedValue(Column $column, ElementInterface $element): mixed
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
