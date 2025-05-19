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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
interface BinaryServiceInterface
{
    /**
     * @throws EnvironmentException|InvalidElementTypeException|StreamResourceNotFoundException
     */
    public function streamPagePreviewImage(Document $document): StreamedResponse;

    /**
     * @throws EnvironmentException
     */
    public function getPagePreviewPath(Page $page): ?string;

    /**
     * @throws EnvironmentException
     */
    public function hasPagePreview(Page $page): bool;
}
