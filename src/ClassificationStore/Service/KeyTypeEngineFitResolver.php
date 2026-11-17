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

use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\KeyTypeSupport;
use Pimcore\Model\DataObject\Exception\ClassificationStoreEngineNotAvailableException;
use Pimcore\Model\DataObject\Exception\UnknownClassificationStoreEngineException;
use function array_filter;
use function array_values;
use function method_exists;

/**
 * @internal
 */
final readonly class KeyTypeEngineFitResolver implements KeyTypeEngineFitResolverInterface
{
    /**
     * Core's answer to what a key's declaration needs of an engine, asked by name for the reason
     * every core method this bundle reaches is optional: `composer.json` allows a core that does not
     * carry it, and there nothing is refused because core's own save refuses nothing either.
     */
    private const string DECLARATION_PROBE = 'requiredEngineFeatures';

    /**
     * Core's answer to the engine a store being created is put on, asked by name for the same
     * reason {@see self::DECLARATION_PROBE} is.
     */
    private const string PREFERRED_ENGINE_PROBE = 'getPreferredEngine';

    public function __construct(
        private ?KeyTypeSupport $keyTypeSupport = null,
        private ?EngineResolver $engineResolver = null,
    ) {
    }

    public function findRefusingEngine(string $type, ?string $delegateType, ?int $storeId): ?string
    {
        if ($this->engineResolver === null
            || $this->keyTypeSupport === null
            || !$this->keyTypeSupport->isAcceptedType($type)
        ) {
            return null;
        }

        if ($this->keyTypeSupport->isHeldInValueColumns($type, $delegateType)) {
            return null;
        }

        $engine = $this->resolveEngine($storeId);
        if ($engine === null) {
            return null;
        }

        if ($engine->supports(EngineFeature::StructuredValues)) {
            return null;
        }

        return $this->engineResolver->nameEngine($engine);
    }

    public function findEngineRefusingDeclaration(string $type, ?array $definition, ?int $storeId): ?array
    {
        if ($this->engineResolver === null
            || $this->keyTypeSupport === null
            || !$this->declaresMethod($this->keyTypeSupport, self::DECLARATION_PROBE)
        ) {
            return null;
        }

        $required = $this->keyTypeSupport->{self::DECLARATION_PROBE}($type, $definition);
        if ($required === []) {
            return null;
        }

        $engine = $this->resolveEngine($storeId);
        if ($engine === null) {
            return null;
        }

        $lacked = [];
        foreach ($required as $feature) {
            if (!$engine->supports($feature)) {
                $lacked[] = $feature->name;
            }
        }

        if ($lacked === []) {
            return null;
        }

        return ['engine' => $this->engineResolver->nameEngine($engine), 'capabilities' => $lacked];
    }

    public function findWithdrawnTypes(array $types): array
    {
        if ($this->keyTypeSupport === null) {
            return [];
        }

        return array_values(array_filter(
            $types,
            fn (string $type): bool => $this->keyTypeSupport->isWithdrawnKeyType($type)
        ));
    }

    public function findDelegateThatIsNotAKeyType(?string $delegateType): ?string
    {
        if ($delegateType === null
            || $this->keyTypeSupport === null
            || $this->keyTypeSupport->isAcceptedDelegateType($delegateType)
        ) {
            return null;
        }

        return $delegateType;
    }

    /**
     * Whether the core object declares the method at all, {@see self::DECLARATION_PROBE}.
     */
    private function declaresMethod(object $subject, string $method): bool
    {
        return method_exists($subject, $method);
    }

    /**
     * The engine behind the store, or null where it cannot be asked: a null store id is a store
     * that does not exist yet, and a store whose engine does not resolve is not guessed at - core's
     * own save names that failure where it happens rather than from here.
     */
    private function resolveEngine(?int $storeId): ?EngineInterface
    {
        if ($this->engineResolver === null) {
            return null;
        }

        try {
            return $storeId === null
                ? $this->engineOfAStoreNotCreatedYet($this->engineResolver)
                : $this->engineResolver->resolveForStore($storeId);
        } catch (UnknownClassificationStoreEngineException | ClassificationStoreEngineNotAvailableException) {
            return null;
        }
    }

    /**
     * The engine a store that does not exist yet would be created on, which is the preferred one
     * and not the default one: a dry run reaches this with no store id because the store it reports
     * as created has no row, and judging its keys against the default engine would refuse what the
     * real apply then accepts. The default engine only where core registers no preferred one, which
     * is also the engine such an installation creates the store on.
     */
    private function engineOfAStoreNotCreatedYet(EngineResolver $engineResolver): EngineInterface
    {
        if (!$this->declaresMethod($engineResolver, self::PREFERRED_ENGINE_PROBE)) {
            return $engineResolver->getDefaultEngine();
        }

        return $engineResolver->{self::PREFERRED_ENGINE_PROBE}() ?? $engineResolver->getDefaultEngine();
    }
}
