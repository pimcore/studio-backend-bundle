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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * Moves restored elements whose parent no longer exists into a fallback folder.
 *
 * @internal
 */
interface FallbackFolderServiceInterface
{
    public const string FOLDER_PATH = '/__restored';

    /**
     * $originalPath is the path of the recycle bin item. Only the item's own element can have a missing parent, core
     * restores its children below it.
     *
     * @throws InvalidElementTypeException
     */
    public function hasMissingParent(ElementInterface $element, string $originalPath): bool;

    /**
     * Sets the fallback folder of the element type as parent and the key from $originalPath, the element's full path
     * at deletion; a taken key gets a number (_1, _2, ...), placed before the extension of an asset file. The user
     * needs the publish permission on the folder, or on the root of the element type while the folder does not exist
     * yet; it is created only after that check.
     *
     * @throws EnvironmentException|InvalidElementTypeException
     */
    public function moveToFallbackFolder(ElementInterface $element, UserInterface $user, string $originalPath): void;
}
