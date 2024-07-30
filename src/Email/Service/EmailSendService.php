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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\EmailAddressParameter;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\SendEmailParameters;
use Pimcore\Bundle\StudioBackendBundle\Email\Util\Constants\EmailAddressType;
use Pimcore\Bundle\StudioBackendBundle\Email\Util\Constants\EmailContentType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Mail;
use Pimcore\Model\UserInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class EmailSendService implements EmailSendServiceInterface
{
    public function __construct(
        private DocumentResolverInterface $documentResolver,
        private EmailLogServiceInterface $emailLogService,
        private MailServiceInterface $mailService,
    ) {
    }

    /**
     * @throws AccessDeniedException
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function sendTestEmail(SendEmailParameters $parameters, UserInterface $user): void
    {
        $mail = new Mail();
        $mail->subject($parameters->getSubject());
        $this->setTestEmailContent($parameters, $mail);
        $this->mailService->setMailFromAddress($parameters->getFrom(), $mail);
        $this->mailService->addMailAddress($parameters->getTo(), EmailAddressType::TO->value, $mail);
        $this->mailService->addMailAttachment($parameters->getAttachmentId(), $mail, $user);
        $mail->setIgnoreDebugMode(true);
        try {
            $mail->send();
        } catch (Exception $exception) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to send test email: %s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function resendEmail(int $id): void
    {
        $emailLogEntry = $this->emailLogService->getEntry($id);
        $mail = $this->emailLogService->getEmailFromLogEntry($emailLogEntry);
        foreach (EmailAddressType::values() as $addressType) {
            $getter = 'get' . ucfirst($addressType);
            $this->mailService->addMailAddress($emailLogEntry->$getter(), $addressType, $mail);
        }
        $mail->disableLogging();

        try {
            $mail->send();
        } catch (Exception $exception) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to resend email: %s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function forwardEmail(int $id, EmailAddressParameter $parameter): void
    {
        $emailLogEntry = $this->emailLogService->getEntry($id);
        $mail = $this->emailLogService->getEmailFromLogEntry($emailLogEntry);
        $this->mailService->addMailAddress($emailLogEntry->getReplyTo(), EmailAddressType::REPLY_TO->value, $mail);
        $this->mailService->addMailAddress($parameter->getEmail(), EmailAddressType::TO->value, $mail);

        try {
            $mail->send();
        } catch (Exception $exception) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to forward email: %s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    private function setTestEmailContent(SendEmailParameters $parameters, Mail $mail): void
    {
        match ($parameters->getContentType()) {
            EmailContentType::HTML->value =>
            $mail->html($parameters->getContent()),
            EmailContentType::TEXT->value =>
            $mail->text($parameters->getContent()),
            EmailContentType::DOCUMENT->value =>
            $this->handleDocumentContentType($parameters, $mail),
            default => throw new EnvironmentException('Invalid content type')
        };
    }

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    private function handleDocumentContentType(SendEmailParameters $parameters, Mail $mail): void
    {
        $document = $this->documentResolver->getByPath($parameters->getDocumentPath());
        if ($document === null) {
            throw new NotFoundException('email document', $parameters->getDocumentPath(), 'path');
        }
        $this->mailService->setMailDocumentContent($document, $mail);

        foreach ($parameters->getDocumentParameters() as $parameter) {
            $mail->setParam($parameter->getKey(), $parameter->getValue());
        }
    }
}
