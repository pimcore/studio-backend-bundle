<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Element\MappedParameter\ElementCloneParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Service as AssetService;

/**
 * @internal
 */
final readonly class CloneService
{
    public function __construct(
        private AssetService $assetService,
        private ElementServiceInterface $elementService,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function cloneElement(
        int $sourceId,
        ElementCloneParameter $elementCloneParameter,
        string $elementType,
    ): void
    {
        $user = $this->securityService->getCurrentUser();
        $source = $this->elementService->getAllowedElementById(
            $elementType,
            $sourceId,
            $user
        );
        $targetId = $elementCloneParameter->getTargetId();
        $target = $this->elementService->getAllowedElementById(
            $elementType,
            $targetId,
            $user
        );

        if (!$target->isAllowed('create')) {
            throw new ForbiddenException(
                sprintf('Missing permissions on target element %s', $targetId));
        }

        if (!$source instanceof Asset || !$target instanceof Asset) {
            throw new InvalidElementTypeException($source->getType());
        }

        try {
            $this->assetService->copyAsChild(
                $target,
                $source,
            );
        } catch (Exception $e) {
            throw new ElementSavingFailedException(
                null,
                $e->getMessage()
            );
        }
    }

}