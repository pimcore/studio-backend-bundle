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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Model\Document\DocType;

/**
 * @internal
 */
interface DocTypeRepositoryInterface
{
    /**
     * @throws InvalidArgumentException
     *
     * @return DocType[]
     */
    public function listDocTypes(?string $type = null): array;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function getById(string $id): DocType;

    /**
     * @throws NotWriteableException
     */
    public function addDocType(): DocType;

    public function getTypesConfiguration(): array;
}
