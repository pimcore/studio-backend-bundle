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

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\PatchFolderParameter;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface PatchServiceInterface
{
    /**
     * @throws ForbiddenException|ElementSavingFailedException|NotFoundException|InvalidArgumentException
     */
    public function patch(
        string $elementType,
        array $patchData,
        UserInterface $user,
    ): ?int;

    /**
     * @throws InvalidArgumentException
     */
    public function patchFolder(
        int $folderId,
        string $elementType,
        PatchFolderParameter $patchFolderParameter,
        UserInterface $user,
    ): int;

    /**
     * @throws ElementSavingFailedException
     */
    public function patchElement(
        ElementInterface $element,
        string $elementType,
        array $elementPatchData,
        UserInterface $user
    ): void;

    /**
     * Merges an add/remove patch ({action, data}) into the existing entries; any other or no action returns the patch
     * data and a plain list is returned unchanged. Entries are matched by element id and type, and "add"
     * never appends an element that is already in the existing entries. With $allowRepeatedEntries false, an element
     * is also kept only once among the existing entries and appended only once from the patch data.
     */
    public function handlePatchDataField(
        array $fieldData,
        array $existingValues,
        ?string $dataKey = null,
        bool $allowRepeatedEntries = true
    ): array;
}
