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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * Holds a write of qualified values to what the engine of the key's store reports it can do.
 *
 * Separate from both the parse ({@see QualifiedValueParserInterface}) and the slot itself
 * ({@see QualifiedValueSerializerInterface}), because it is the one question here that the key's
 * declaration does not answer: a declaration is accepted whatever the engine behind it is, and it
 * is the *value write* that has to be refused when the engine cannot store what it addresses.
 *
 * @internal
 */
interface EngineCapabilityGuardInterface
{
    /**
     * Refuses a write that needs a capability the store's engine does not report.
     *
     * Fail-closed: a capability set of null is a core that could not be asked, and a capability
     * that could not be confirmed is one the write does not get - it counts the same here as the
     * empty set an engine reporting none answers with
     * ({@see StoreEngineResolverInterface::getCapabilitiesOfStore()}). The two stay separate answers
     * where they are reported rather than acted on.
     *
     * Either way only a write that actually needs a capability is refused: one entry carrying no
     * variant at position 0 needs neither and goes through against any answer.
     *
     * @param QualifiedValue[] $values
     * @param string[]|null $capabilities
     *
     * @throws InvalidArgumentException
     */
    public function assertSupported(array $values, ?array $capabilities): void;
}
