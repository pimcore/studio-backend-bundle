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

use Pimcore\Bundle\StaticResolverBundle\Models\Metadata\Predefined\PredefinedResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Repository\MetadataRepositoryInterface;
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
final class MetadataAdapter implements PatchAdapterInterface
{
    private const INDEX_KEY = 'metadata';

    private const PATCHABLE_KEYS = [
        'language',
        'data',
    ];

    public function __construct(private readonly MetadataRepositoryInterface $metadataRepository)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function patch(ElementInterface $element, array $data): void
    {
        if (!$element instanceof Asset || !isset($data[self::INDEX_KEY])) {
            return;
        }

        $metadataForPatch = $data[self::INDEX_KEY];
        $currentMetadata = $element->getMetadata(null, null, false, true);
        $patchedMetadata = [];

        foreach ($currentMetadata as $metadata) {
            $index = array_search($metadata['name'], array_column($metadataForPatch, 'name'), true);

            if ($index === false) {
                $patchedMetadata[] = $metadata;

                continue;
            }

            // check for every single metadata if it is in the patch data
            foreach (self::PATCHABLE_KEYS as $patchKeys) {
                if (array_key_exists($patchKeys, $metadataForPatch[$index])) {
                    $metadata[$patchKeys] = $metadataForPatch[$index][$patchKeys];
                }
            }
            $patchedMetadata[] = $metadata;

            // unset them, everything that is still in there needs to be added
            unset($metadataForPatch[$index]);
        }

        $patchedMetadata = [
            ...$patchedMetadata,
            ...array_map(
                fn (array $metaData) => $this->processNewMetadataEntry($metaData),
                $metadataForPatch
            ),
        ];

        if (!empty($patchedMetadata)) {
            $element->setMetadata($patchedMetadata);
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

    /**
     * @throws InvalidArgumentException
     */
    private function processNewMetadataEntry(array $metadata): array
    {
        if (!isset($metadata['name'])) {
            throw new InvalidArgumentException('Metadata name is required');
        }

        $predefined = $this->metadataRepository->getPredefinedMetadataByName($metadata['name']);

        if (!$predefined) {
            throw new InvalidArgumentException('Predefined metadata not found');
        }

        return [
            'name' => $predefined->getName(),
            'language' => $metadata['language'] ?? null,
            'type' => $predefined->getType(),
            'data' => $metadata['data'] ?? null,
        ];
    }
}
