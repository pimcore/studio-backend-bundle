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

/**
 * The one place the questions core's key save asks of a key's field type are put from this bundle:
 * is it withdrawn, is its delegate a key type, can the engine of the store hold it. The same
 * conditions, in the same order, as
 * {@see \Pimcore\Model\DataObject\Classificationstore\KeyConfig::save()}, so that a write which
 * would be refused there is named before it is attempted.
 *
 * @internal
 */
interface KeyTypeEngineFitResolverInterface
{
    /**
     * The name of the engine that cannot hold the type, or null where the type is held.
     *
     * Only a type of the accepted set is governed, one held in the two value columns fits every
     * engine, and an engine reporting the `StructuredValues` capability holds the rest. A null
     * `$storeId` is a store that does not exist yet, which will carry the default engine. An
     * installation that offers no engine resolver cannot be asked, and core's own gate cannot
     * refuse there either, so nothing is refused; the same holds for a store whose engine cannot be
     * resolved, which is not thrown from here.
     */
    public function findRefusingEngine(string $type, ?string $delegateType, ?int $storeId): ?string;

    /**
     * The engine that cannot address what the key's DECLARATION promises, together with the
     * capability names it lacks of it - or null where the declaration needs nothing the engine does
     * not offer.
     *
     * A second question from the one above, and core asks it separately for the same reason
     * ({@see \Pimcore\Model\DataObject\Classificationstore\KeyTypeSupport::requiredEngineFeatures()}):
     * a `multiValue` wrapping an `input` IS held in the two value columns, because each of its slots
     * holds an input, and what the EAV row has no column for is the qualifier saying which slot.
     *
     * Which of the named capabilities a key may still carry is for the caller to say, exactly as
     * with the withdrawn types below: core's key save refuses only a capability the stored row does
     * not already need, so that a key an installation defined before the refusal existed stays
     * editable and removable. An installation that cannot be asked refuses nothing.
     *
     * @param array<string, mixed>|null $definition the definition blob of the key, decoded
     *
     * @return array{engine: string, capabilities: string[]}|null
     */
    public function findEngineRefusingDeclaration(string $type, ?array $definition, ?int $storeId): ?array;

    /**
     * The field types among the given ones that have been withdrawn as a key type, in order. Which
     * of them a key may still carry is for the caller to say: core's key save refuses only a name
     * the stored row does not already carry. An installation without the type support is not asked.
     *
     * @param string[] $types
     *
     * @return string[]
     */
    public function findWithdrawnTypes(array $types): array;

    /**
     * The delegate type that is not a field type a key may be defined with, or null where it is one
     * or no delegate is given. Core asks this before it asks what the engine can hold.
     */
    public function findDelegateThatIsNotAKeyType(?string $delegateType): ?string;
}
