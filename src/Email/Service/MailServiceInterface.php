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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Mail;
use Pimcore\Model\Document;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface MailServiceInterface
{
    /**
     * @throws EnvironmentException
     */
    public function setMailFromAddress(string $from, Mail $mail): void;

    /**
     * @throws EnvironmentException
     */
    public function addMailAddress(?string $address, string $addressType, Mail $mail): void;

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function setMailDocumentContent(Document $document, Mail $mail): void;

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    public function addMailAttachment(?int $attachmentId, Mail $mail, UserInterface $user): void;
}
