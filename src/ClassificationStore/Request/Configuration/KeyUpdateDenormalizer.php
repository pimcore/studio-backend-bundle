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
use function is_array;
use function is_string;
use function sprintf;

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
        // A JSON body that is not an object arrives here as a scalar, so the shape is checked
        // before anything indexes it: without this the field reads below raise a TypeError, and
        // ApiExceptionSubscriber forwards only HTTP exceptions, so the client would see a 500.
        if (!is_array($data)) {
            throw new InvalidArgumentException('The request payload must be an object.');
        }

        return new KeyUpdate(
            // Left to the constructor guard, so a missing name stays a 422 instead of a TypeError.
            name: $this->stringOrNull($data, 'name') ?? '',
            title: $this->stringOrNull($data, 'title'),
            description: $this->stringOrNull($data, 'description'),
            type: $this->stringOrNull($data, 'type'),
            definition: $this->arrayOrNull($data, 'definition'),
            titleSupplied: array_key_exists('title', $data),
            descriptionSupplied: array_key_exists('description', $data),
        );
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws InvalidArgumentException
     */
    private function stringOrNull(array $data, string $field): ?string
    {
        $value = $data[$field] ?? null;

        if ($value !== null && !is_string($value)) {
            throw new InvalidArgumentException(sprintf('`%s` must be a string or null.', $field));
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>|null
     *
     * @throws InvalidArgumentException
     */
    private function arrayOrNull(array $data, string $field): ?array
    {
        $value = $data[$field] ?? null;

        if ($value !== null && !is_array($value)) {
            throw new InvalidArgumentException(sprintf('`%s` must be an object or null.', $field));
        }

        return $value;
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
