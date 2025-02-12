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

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Model\Document as DocumentModel;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface DocumentServiceInterface
{
    /**
     * @throws SearchException|NotFoundException|UserNotFoundException
     */
    public function getDocument(int $id, bool $getWorkflowAvailable = true): Document;

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDocumentForUser(int $id, UserInterface $user): Document;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getDocumentElement(UserInterface $user, int $documentId): DocumentModel;
}
