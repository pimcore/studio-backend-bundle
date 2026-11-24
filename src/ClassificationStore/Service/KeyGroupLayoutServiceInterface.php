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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\VariantLayout;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
interface KeyGroupLayoutServiceInterface
{
    /**
     * @throws Exception
     */
    public function getLayoutDefinition(
        KeyGroupRelation $keyGroupRelation,
        string $fieldName,
        ?Concrete $object = null,
    ): EncryptedField|Data;

    public function getKeyLayout(
        LayoutParameter $layoutParameter,
        int $keyId,
        int $groupId
    ): KeyLayout;

    /**
     * Reads the labels of the given keys with a single statement, so that the getKeyTitle() calls
     * that follow cost nothing.
     *
     * @param int[] $keyIds
     */
    public function preloadKeyTitles(array $keyIds, string $language): void;

    /**
     * Returns the label of the key configuration resolved for $language: the title stored for a
     * language of the chain, then the key's own title, then null - never its technical name.
     */
    public function getKeyTitle(int $keyId, string $language): ?string;

    /**
     * The key's own `localized` flag, taken off the key configuration the title resolution already
     * loads - core serves that second read from its runtime cache, so no key costs a second query -
     * and null for a key that states none or cannot be loaded at all.
     */
    public function getKeyLocalized(int $keyId): ?bool;

    /**
     * The three members a key layout publishes about its qualified values, resolved off a key
     * configuration the caller already holds, so that a caller enriching a whole group pays no key
     * configuration read at all. The one place the keyId accessors below answer from.
     */
    public function getKeyQualifiers(KeyConfig $keyConfig): KeyQualifiers;

    /**
     * The variants a `multiValue` key declares, in the order it declares them, taken off the same key
     * configuration for the same reason, and null for a key of any other type or one that cannot be
     * loaded at all.
     *
     * @return VariantLayout[]|null
     */
    public function getKeyVariants(int $keyId): ?array;

    /**
     * Whether a `multiValue` key permits a value carrying no variant, taken off the same key
     * configuration, and null for a key that is not of type `multiValue` or cannot be loaded at all.
     */
    public function getKeyDefaultVariant(int $keyId): ?bool;

    /**
     * The label of the default variant of a `multiValue` key, and null where it declares none, for a
     * key of any other type, or one that cannot be loaded.
     */
    public function getKeyDefaultVariantLabel(int $keyId): ?string;
}
