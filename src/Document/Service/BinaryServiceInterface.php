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
