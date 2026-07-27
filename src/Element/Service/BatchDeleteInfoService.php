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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\DeleteInfo;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\IdsParameter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\UserInterface;
use function in_array;

/**
 * @internal
 */
final readonly class BatchDeleteInfoService implements BatchDeleteInfoServiceInterface
{
    public function __construct(
        private ElementServiceInterface $elementService,
        private ElementDeleteServiceInterface $elementDeleteService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getBatchDeleteInfo(
        IdsParameter $ids,
        string $elementType,
        UserInterface $user
    ): DeleteInfo {
        if (!in_array($elementType, ElementTypes::ALLOWED_STUDIO_TYPES, true)) {
            throw new InvalidElementTypeException($elementType);
        }

        $hasDependencies = false;
        $canUseRecycleBin = true;

        foreach ($ids->getIds() as $id) {
            // hasDependencies can only flip to true, canUseRecycleBin only to false: once both are
            // final there is nothing left to learn, so skip the remaining elements.
            if ($hasDependencies && !$canUseRecycleBin) {
                break;
            }

            $element = $this->elementService->getAllowedElementById($elementType, $id, $user);
            $deleteInfo = $this->elementDeleteService->getElementDeleteInfo($element, $user);

            $hasDependencies = $hasDependencies || $deleteInfo->getHasDependencies();
            $canUseRecycleBin = $canUseRecycleBin && $deleteInfo->getCanUseRecycleBin();
        }

        return new DeleteInfo($hasDependencies, $canUseRecycleBin);
    }
}
