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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocType;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocTypeAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocTypeUpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;

/**
 * @internal
 */
interface DocTypeServiceInterface
{
    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotWriteableException
     */
    public function addDoctype(DocTypeAddParameters $parameters): DocType;

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotWriteableException|NotFoundException
     */
    public function updateDoctype(string $id, DocTypeUpdateParameters $parameters): DocType;

    /**
     * @throws InvalidArgumentException
     *
     * @return DocType[]
     */
    public function listDocTypes(?string $type): array;
}
