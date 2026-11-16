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
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\KeyTypeSupport;
use Pimcore\Model\DataObject\Exception\ClassificationStoreEngineNotAvailableException;
use Pimcore\Model\DataObject\Exception\UnknownClassificationStoreEngineException;

/**
 * @internal
 */
final readonly class KeyTypeEngineFitResolver implements KeyTypeEngineFitResolverInterface
{
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

        try {
            $engine = $storeId === null
                ? $this->engineResolver->getDefaultEngine()
                : $this->engineResolver->resolveForStore($storeId);
        } catch (UnknownClassificationStoreEngineException | ClassificationStoreEngineNotAvailableException) {
            // a store whose engine cannot be resolved cannot be asked; core's own save names that failure
            return null;
        }

        if ($engine->supports(EngineFeature::StructuredValues)) {
            return null;
        }

        return $this->engineResolver->nameEngine($engine);
    }
}
