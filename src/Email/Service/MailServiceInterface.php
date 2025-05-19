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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
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
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function addMailAttachment(?int $attachmentId, Mail $mail, UserInterface $user): void;
}
