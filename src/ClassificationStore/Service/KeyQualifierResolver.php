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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use ReflectionProperty;
use Symfony\Contracts\Service\ResetInterface;
use function array_key_exists;
use function hash;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function method_exists;

/**
 * @internal
 */
final class KeyQualifierResolver implements KeyQualifierResolverInterface, ResetInterface
{
    private const string TYPE_PROPERTY = 'type';

    // Core's own accessors for the declaration, on Data\MultiValue. Named here rather than
    // called directly only so a core without them resolves to the permitted default below.
    private const string DEFAULT_VARIANT_PERMITTED_GETTER = 'permitsUnqualifiedValue';

    private const string DEFAULT_VARIANT_LABEL_GETTER = 'getUnqualifiedValueLabel';

    /**
     * The digest the memo key spells a `definition` blob as. Deliberately a NON-cryptographic hash:
     * the memo key only has to tell two blobs apart, it is never stored, compared against a secret
     * or shown to anyone, and a cryptographic digest here reads to a security review as a weak hash
     * standing in for one that matters.
     */
    private const string MEMO_HASH = 'xxh128';

    /**
     * The declaration of every key already resolved, keyed by the key's type and the hash of its
     * `definition` blob - the two the resolution reads and nothing else.
     *
     * Rebuilding a whole core field definition from the blob is what every read here costs, and the
     * same key is asked repeatedly: one group layout asks three times per key (its variants, its
     * `multiValue` flag, its maximum) and the object data path asks again per key and language, on
     * the read and on the write.
     *
     * Keyed by what it is resolved FROM rather than by the key's id, so nothing has to invalidate
     * it: a configuration write that changes a key's type or its blob resolves another entry, and
     * one that changes neither cannot change the declaration.
     *
     * @var array<string, MultiValue|null>
     */
    private array $declarations = [];

    public function __construct(
        private readonly ServiceResolverInterface $serviceResolver,
    ) {
    }

    /**
     * Drops the memo, so that a long-running worker does not carry the declarations of every key it
     * has ever seen - implementing the interface is what tags this service `kernel.reset`.
     */
    public function reset(): void
    {
        $this->declarations = [];
    }

    public function declaresQualifiers(KeyConfig $keyConfig): bool
    {
        return $this->getDeclaration($keyConfig) !== null;
    }

    public function getVariants(KeyConfig $keyConfig): ?array
    {
        return $this->getDeclaration($keyConfig)?->getVariantNames();
    }

    public function getVariantDeclarations(KeyConfig $keyConfig): ?array
    {
        return $this->getDeclaration($keyConfig)?->getVariants();
    }

    public function isMultiValue(KeyConfig $keyConfig): ?bool
    {
        return $this->getDeclaration($keyConfig)?->isMultiValue();
    }

    public function getMaximum(KeyConfig $keyConfig): ?int
    {
        return $this->getDeclaration($keyConfig)?->getMaximum();
    }

    public function getDefaultVariant(KeyConfig $keyConfig): ?DefaultVariant
    {
        $declaration = $this->getDeclaration($keyConfig);
        if ($declaration === null) {
            return null;
        }

        $permitted = $this->readAccessor($declaration, self::DEFAULT_VARIANT_PERMITTED_GETTER);
        $label = $this->readAccessor($declaration, self::DEFAULT_VARIANT_LABEL_GETTER);

        return new DefaultVariant(
            is_bool($permitted) ? $permitted : true,
            is_string($label) ? $label : null
        );
    }

    /**
     * The accessor by name: `composer.json` allows a core whose `multiValue` declares no default
     * variant setting, and against one of those the answer is the one a blob omitting it gets.
     */
    private function readAccessor(MultiValue $declaration, string $accessor): mixed
    {
        return method_exists($declaration, $accessor) ? $declaration->$accessor() : null;
    }

    /**
     * The key's field definition, resolved from its type and its `definition` blob, when that is a
     * core `multiValue` definition. A blob that is not readable as JSON declares nothing.
     *
     * Resolved once per (type, blob) and served from {@see self::$declarations} afterwards.
     */
    private function getDeclaration(KeyConfig $keyConfig): ?MultiValue
    {
        // `$type` has no default: a key config that was never hydrated has no type to resolve by
        if (!(new ReflectionProperty($keyConfig, self::TYPE_PROPERTY))->isInitialized($keyConfig)) {
            return null;
        }

        $memoKey = $keyConfig->getType() . "\0" . hash(self::MEMO_HASH, $keyConfig->getDefinition());
        if (array_key_exists($memoKey, $this->declarations)) {
            return $this->declarations[$memoKey];
        }

        $declaration = $this->resolveDeclaration($keyConfig);
        $this->declarations[$memoKey] = $declaration;

        return $declaration;
    }

    /**
     * @see self::getDeclaration()
     */
    private function resolveDeclaration(KeyConfig $keyConfig): ?MultiValue
    {
        try {
            $decoded = json_decode($keyConfig->getDefinition(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        $definition = $this->serviceResolver->getFieldDefinitionFromJson($decoded, $keyConfig->getType());

        return $definition instanceof MultiValue ? $definition : null;
    }
}
