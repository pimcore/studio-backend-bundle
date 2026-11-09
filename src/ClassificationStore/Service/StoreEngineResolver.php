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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use function enum_exists;
use function is_string;
use function method_exists;
use function sprintf;

/**
 * @internal
 */
final readonly class StoreEngineResolver implements StoreEngineResolverInterface
{
    private const string ENGINE_GETTER = 'getEngine';

    public function __construct(
        private ?EngineResolver $engineResolver = null,
    ) {
    }

    public function getEngine(StoreConfig $storeConfig): ?string
    {
        if (!$this->declaresAccessor(self::ENGINE_GETTER)) {
            return null;
        }

        return $this->readEngine($storeConfig);
    }

    public function getEngineCapabilities(StoreConfig $storeConfig): ?array
    {
        if ($this->engineResolver === null || !$this->declaresAccessor(self::ENGINE_GETTER)) {
            return null;
        }

        try {
            // the engine value already loaded, so reading a whole store tree adds no query
            $engine = $this->engineResolver->resolve($this->readEngine($storeConfig), (int) $storeConfig->getId());
        } catch (Exception) {
            return null;
        }

        return $this->featuresOf($engine);
    }

    public function getCapabilitiesOfStore(int $storeId): ?array
    {
        if ($this->engineResolver === null) {
            return null;
        }

        try {
            $engine = $this->engineResolver->resolveForStore($storeId);
        } catch (Exception $exception) {
            // the failure it is: reported as the empty set it would say the engine supports nothing
            throw new EnvironmentException(
                sprintf('The engine of the classification store %d could not be resolved.', $storeId),
                previous: $exception
            );
        }

        return $this->featuresOf($engine);
    }

    /**
     * {@see self::getEngine()} against any store configuration model.
     *
     * Public, and typed on the model rather than on `StoreConfig`, so that both halves of the
     * bridge can be covered: `StoreConfig` is final and the core this bundle resolves need not
     * declare the accessor at all, so a real one can only ever exercise one half. Production call
     * sites bind to {@see StoreEngineResolverInterface} and never reach this.
     */
    public function readEngine(object $storeConfig): ?string
    {
        if (!method_exists($storeConfig, self::ENGINE_GETTER)) {
            return null;
        }

        $engine = $storeConfig->{self::ENGINE_GETTER}();

        return is_string($engine) ? $engine : null;
    }

    /**
     * Exactly those capability names the engine answers true for, in the order the core enum
     * declares its cases and spelled the way it spells them.
     *
     * Null rather than an empty list where the question could not be put at all: a core that does
     * not carry the capability enum has no capability to report, and "no answer" and "no
     * capabilities" are two different answers a caller tells apart. The enum is the one thing asked
     * for by name here - `supports()` is declared by the interface the engine is typed on, so an
     * engine reaching this point answers it.
     *
     * Public, and taking the engine rather than the store, so that what this bundle makes of each
     * answer can be read against an engine of a known mind: the step before it is the core's own
     * resolution of a stored store id, which cannot be driven without a database.
     * {@see self::getCapabilitiesOfStore()} and {@see self::getEngineCapabilities()} are its
     * production callers.
     *
     * @return string[]|null
     */
    public function featuresOf(EngineInterface $engine): ?array
    {
        if (!enum_exists(EngineFeature::class)) {
            return null;
        }

        $supported = [];
        foreach (EngineFeature::cases() as $feature) {
            if ($engine->supports($feature)) {
                $supported[] = $feature->name;
            }
        }

        return $supported;
    }

    /**
     * Whether the core's store configuration declares the accessor at all. Asked by name, for the
     * reason {@see self::readEngine()} asks by name: the method is not this package's and the core
     * whose it is need not have it.
     */
    private function declaresAccessor(string $accessor): bool
    {
        return method_exists(StoreConfig::class, $accessor);
    }
}
