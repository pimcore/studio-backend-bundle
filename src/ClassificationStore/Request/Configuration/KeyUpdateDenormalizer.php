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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\AbsentValue;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\JsonObjectShapeTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function array_key_exists;
use function is_array;
use function is_bool;
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
    use JsonObjectShapeTrait;

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
            localized: $this->boolOrNull($data, 'localized'),
            customMetadata: $this->objectOrNull($data, 'customMetadata'),
            titleSupplied: array_key_exists('title', $data),
            descriptionSupplied: array_key_exists('description', $data),
            localizedSupplied: array_key_exists('localized', $data),
            customMetadataSupplied: array_key_exists('customMetadata', $data),
            // Forwarded so the schema's refusal still fires: this denormalizer replaces the
            // mapping that used to hand a supplied `identifier` to the constructor.
            identifier: array_key_exists('identifier', $data) ? $data['identifier'] : AbsentValue::ABSENT,
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
     * @throws InvalidArgumentException
     */
    private function boolOrNull(array $data, string $field): ?bool
    {
        $value = $data[$field] ?? null;

        if ($value !== null && !is_bool($value)) {
            throw new InvalidArgumentException(sprintf('`%s` must be a boolean or null.', $field));
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

    /**
     * The same read as {@see self::arrayOrNull()}, but for a member whose contract is an `object`
     * rather than any array: a non-empty JSON list is refused here instead of being written under
     * it ({@see JsonObjectShapeTrait}). `definition` deliberately keeps the laxer read - its
     * `object` contract is already released, so tightening it is a change of its own.
     *
     * @param array<array-key, mixed> $data
     *
     * @return array<string, mixed>|null
     *
     * @throws InvalidArgumentException
     */
    private function objectOrNull(array $data, string $field): ?array
    {
        $value = $data[$field] ?? null;

        if ($value !== null && !$this->isJsonObject($value)) {
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
