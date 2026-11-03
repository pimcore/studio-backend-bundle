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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Request\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function array_key_exists;

/**
 * The payload keys are only visible here: the mapped DTO cannot tell an absent `title` from a
 * `title` sent as null, which is the difference between leaving the stored title alone and clearing it.
 *
 * @internal
 */
final readonly class KeyUpdateDenormalizer implements DenormalizerInterface, NormalizerInterface
{
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type === KeyUpdate::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            KeyUpdate::class => true,
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function denormalize(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): KeyUpdate {
        return new KeyUpdate(
            // Left to the constructor guard, so a missing name stays a 422 instead of a TypeError.
            name: $data['name'] ?? '',
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            type: $data['type'] ?? null,
            definition: $data['definition'] ?? null,
            titleSupplied: array_key_exists('title', $data),
            descriptionSupplied: array_key_exists('description', $data),
        );
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return false;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): null
    {
        return null;
    }
}
