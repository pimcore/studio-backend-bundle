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

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\PatchFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface PatchServiceInterface
{
    /**
     * @throws AccessDeniedException|ElementSavingFailedException|NotFoundException|InvalidArgumentException
     */
    public function patch(
        string $elementType,
        array $patchData,
        UserInterface $user,
    ): ?int;

    /**
     * @throws AccessDeniedException|ElementSavingFailedException|NotFoundException|InvalidArgumentException
     */
    public function patchFolder(
        string $elementType,
        PatchFolderParameter $patchFolderParameter,
        UserInterface $user,
    ): ?int;

    /**
     * @throws ElementSavingFailedException
     */
    public function patchElement(
        ElementInterface $element,
        string $elementType,
        array $elementPatchData,
        UserInterface $user
    ): void;
}
