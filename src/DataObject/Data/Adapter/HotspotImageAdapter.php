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

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\Element\Data\MarkerHotspotItem;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class HotspotImageAdapter implements SetterDataInterface
{
    use ElementProviderTrait;

    public function __construct(private ServiceResolverInterface $serviceResolver)
    {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?Hotspotimage
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $data = $data[$key];
        $data['marker'] = $this->processData($data['marker'] ?? []);
        $data['hotspots'] = $this->processData($data['hotspots'] ?? []);

        if (!empty($data['id']) && (int)$data['id'] > 0) {
            return new Hotspotimage(
                $data['id'],
                $data['hotspots'],
                $data['marker'],
                $data['crop'] ?? []
            );
        }

        return null;
    }
    private function processData(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        foreach ($data as &$element) {
            if (isset($element['data']) && is_array($element['data']) && $element['data'] !== []) {
                $element['data'] = $this->processMetaData($element['data']);
            }
        }

        return $data;
    }

    private function processMetaData(array $metaData): array
    {
        foreach ($metaData as &$item) {
            $item = new MarkerHotspotItem($item);
            if ($this->isValidItem($item)) {
                try {
                    $element = $this->getElementByPath($this->serviceResolver, $item['type'], $item->getValue());
                } catch (NotFoundException) {
                    continue;
                }

                $item['value'] = $element;
            }
        }

        return $metaData;
    }

    private function isValidItem(MarkerHotspotItem $item): bool
    {
        return in_array(
            $item['type'],
            [ElementTypes::TYPE_ASSET, ElementTypes::TYPE_DOCUMENT, ElementTypes::TYPE_OBJECT],
            true
            ) && $item->getValue();
    }
}
