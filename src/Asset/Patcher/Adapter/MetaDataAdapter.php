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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Patcher\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\PatchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorAdapter::ADAPTER_TAG)]
final class MetaDataAdapter implements PatchAdapterInterface
{
    private const INDEX_KEY = 'metadata';

    private const PATCHABLE_KEYS = [
        'language',
        'data',
    ];

    public function patch(ElementInterface $element, array $data): void
    {
        if (!$element instanceof Asset || !isset($data[self::INDEX_KEY])) {
            return;
        }

        $metaDataForPatch = $data[self::INDEX_KEY];
        $currentMetaData = $element->getMetadata();
        $patchedMetaData = [];

        foreach ($currentMetaData as $metaData) {
            $index = array_search($metaData['name'], array_column($metaDataForPatch, 'name'), true);

            if ($index === false) {
                $patchedMetaData[] = $metaData;

                continue;
            }

            // check for every single metaData if it is in the patch data
            foreach (self::PATCHABLE_KEYS as $patchKeys) {
                if (array_key_exists($patchKeys, $metaDataForPatch[$index])) {
                    $metaData[$patchKeys] = $metaDataForPatch[$index][$patchKeys];
                }
            }
            $patchedMetaData[] = $metaData;
        }

        if (!empty($patchedMetaData)) {
            $element->setMetadata($patchedMetaData);
        }
    }

    public function getIndexKey(): string
    {
        return self::INDEX_KEY;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }
}
