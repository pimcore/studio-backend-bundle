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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Patcher\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Repository\MetadataRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\DataResolverServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\MetadataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Adapter\PatchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;
use function in_array;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorAdapter::ADAPTER_TAG)]
final class CustomMetadataAdapter implements PatchAdapterInterface
{
    private const string INDEX_KEY = 'metadata';

    private const array PATCHABLE_KEYS = [
        'language',
        'data',
    ];

    public function __construct(
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService,
        private readonly DataResolverServiceInterface $dataResolverService,
        private readonly MetadataRepositoryInterface $metadataRepository
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function patch(ElementInterface $element, array $data, UserInterface $user): void
    {
        if (!$element instanceof Asset || !isset($data[self::INDEX_KEY])) {
            return;
        }

        $metadataForPatch = $data[self::INDEX_KEY];
        $currentMetadata = $element->getMetadata(raw: true);
        $patchedMetadata = [];

        foreach ($currentMetadata as $metadata) {
            $index = $this->findIndexOfMatch($metadata, $metadataForPatch);
            if ($index === false) {
                $patchedMetadata[] = $metadata;

                continue;
            }

            foreach (self::PATCHABLE_KEYS as $patchKey) {
                if (array_key_exists($patchKey, $metadataForPatch[$index])) {
                    $metadata[$patchKey] = $this->getExistingEntryValue(
                        $metadataForPatch[$index],
                        $metadata,
                        $patchKey,
                        $user
                    );
                }
            }
            $patchedMetadata[] = $metadata;

            // unset them, everything that is still in there, needs to be added
            unset($metadataForPatch[$index]);
        }

        $patchedMetadata = [
            ...$patchedMetadata,
            ...array_map(
                fn (array $metaData) => $this->processNewMetadataEntry($metaData, $user),
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

    private function getExistingEntryValue(
        array $metadata,
        array $existingMetadata,
        string $key,
        UserInterface $user
    ): mixed {
        if ($key !== 'data') {
            return $metadata[$key];
        }

        return $this->dataResolverService->denormalizeData(
            $metadata,
            $user,
            $existingMetadata['type'],
            $existingMetadata,
            true
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function processNewMetadataEntry(array $metadata, UserInterface $user): array
    {
        if (!isset($metadata['name'])) {
            throw new InvalidArgumentException('Metadata name is required');
        }

        if (in_array($metadata['name'], MetadataServiceInterface::DEFAULT_METADATA, true)) {
            return $this->addDefaultMetadata($metadata);
        }

        $predefined = $this->metadataRepository->getPredefinedMetadataByName($metadata['name']);
        $type = $predefined?->getType();
        if (!$type) {
            $columnConfigurations = $this->columnConfigurationService->getAvailableAssetColumnConfiguration();
            foreach ($columnConfigurations as $columnConfiguration) {
                if ($metadata['name'] === $columnConfiguration->getKey()) {
                    $definition = $columnConfiguration->getConfig()['definition'] ?? null;
                    $type = $definition?->getFieldtype();

                    break;
                }
            }

            if (!$type) {
                throw new InvalidArgumentException(sprintf('Asset metadata %s not found', $metadata['name']));
            }
        }

        return [
            'name' => $metadata['name'],
            'language' => $metadata['language'] ?? '',
            'type' => $type,
            'data' => $metadata['data'] ?
                $this->dataResolverService->denormalizeData($metadata, $user, $type, [], true) :
                null,
        ];
    }

    private function findIndexOfMatch(array $metadata, array $patchMetadata): int|bool
    {
        // Try to find a match. array_filter keeps the original index which we can get with array_keys
        $match = array_keys(array_filter($patchMetadata, static function ($patch) use ($metadata) {
            $language = $patch['language'] ?? '';

            return $patch['name'] === $metadata['name'] && $language === $metadata['language'];
        }));

        // Return the key of the first match
        return !empty($match) ? current($match) : false;
    }

    private function addDefaultMetadata(array $metadata): array
    {
        return [
            'name' => $metadata['name'],
            'language' => $metadata['language'] ?? '',
            'type' => 'input',
            'data' => $metadata['data'] ?? null,
        ];
    }
}
