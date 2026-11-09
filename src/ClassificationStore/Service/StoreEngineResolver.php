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
use function enum_exists;
use function sprintf;

/**
 * @internal
 */
final readonly class StoreEngineResolver implements StoreEngineResolverInterface
{
    public function __construct(
        private ?EngineResolver $engineResolver = null,
    ) {
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
     * {@see self::getCapabilitiesOfStore()} is its production caller.
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
}
