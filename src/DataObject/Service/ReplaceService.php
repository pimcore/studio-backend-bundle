<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ReplaceService implements ReplaceServiceInterface
{
    private DataObjectService $coreDataObjectService;

    public function __construct(
        private readonly DataObjectServiceInterface $dataObjectService,
        private readonly SecurityServiceInterface $securityService,
    ) {
        $this->coreDataObjectService = new DataObjectService();
    }

    /**
     * @throws AccessDeniedException
     * @throws ElementSavingFailedException
     * @throws InvalidElementTypeException
     * @throws UserNotFoundException
     * @throws NotFoundException
     */
    public function replaceContents(
        int $sourceId,
        int $targetId,
    ): void {
        $user = $this->securityService->getCurrentUser();
        $source = $this->getConcreteElement($user, $sourceId);
        $target = $this->getConcreteElement($user, $targetId);
        $this->securityService->hasElementPermission($target, $user, ElementPermissions::CREATE_PERMISSION);

        try {
            if ($source->getLatestVersion()) {
                $source = $source->getLatestVersion()->loadData();
                $source->setPublished(false);
            }
            $this->coreDataObjectService->copyContents($target, $source);
        } catch (Exception $e) {
            throw new ElementSavingFailedException($targetId, $e->getMessage());
        }
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    private function getConcreteElement(UserInterface $user, int $id): Concrete
    {
        $element = $this->dataObjectService->getDataObjectElement($user, $id);
        if (!$element instanceof Concrete) {
            throw new InvalidElementTypeException($element->getType());
        }

        return $element;
    }
}
