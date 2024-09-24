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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\RgbaColor;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\RgbaColor as RgbaColorData;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class
RgbaColorAdapter implements DataAdapterInterface
{
    /**
     * @throws Exception
     */
    public function getDataForSetter(Concrete $element, Data $fieldDefinition, string $key, array $data): mixed
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $colorData = trim($data[$key], '# ');
        [$r, $g, $b, $a] = sscanf($colorData, '%02x%02x%02x%02x');

        return new RgbaColorData($r, $g, $b, $a);
    }

    public function supports(string $fieldDefinitionClass): bool
    {
        return $fieldDefinitionClass === RgbaColor::class;
    }
}
