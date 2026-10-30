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
     * @throws InvalidElementTypeException
     */
    public function hasMissingParent(ElementInterface $element): bool;

    /**
     * Sets the fallback folder of the element type as parent and makes the key unique in it.
     *
     * @throws EnvironmentException|InvalidElementTypeException
     */
    public function moveToFallbackFolder(ElementInterface $element, UserInterface $user): void;
}
