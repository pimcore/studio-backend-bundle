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
use Pimcore\Bundle\StaticResolverBundle\Lib\Helper\MailResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Email\Event\PreResponse\EmailLogEntryEvent;
use Pimcore\Bundle\StudioBackendBundle\Email\Repository\EmailLogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\EmailLogEntry;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\TestEmailRequest;
use Pimcore\Bundle\StudioBackendBundle\Email\Util\Constants\TestEmailContentType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Mail;
use Pimcore\Model\Document\Email;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\Address;
use function sprintf;

/**
 * @internal
 */
final readonly class EmailService implements EmailServiceInterface
{
    public function __construct(
        private AssetServiceInterface $assetService,
        private DocumentResolverInterface $documentResolver,
        private EmailLogRepositoryInterface $emailLogRepository,
        private EventDispatcherInterface $eventDispatcher,
        private MailResolverInterface $mailResolver
    ) {
    }

    public function listEntries(CollectionParameters $parameters): Collection
    {
        $list = [];
        $listing = $this->emailLogRepository->getListing($parameters);
        foreach ($listing as $listEntry) {
            $entry = new EmailLogEntry(
                $listEntry->getId(),
                $listEntry->getFrom(),
                $listEntry->getTo(),
                $listEntry->getSubject(),
                $listEntry->getSentDate(),
                $listEntry->getError()
            );

            $this->eventDispatcher->dispatch(
                new EmailLogEntryEvent($entry)
            );

            $list[] = $entry;
        }

        return new Collection(
            $listing->getTotalCount(),
            $list
        );
    }

    /**
     * @throws AccessDeniedException
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function sendTestEmail(TestEmailRequest $parameters, UserInterface $user): void
    {
        $mail = new Mail();
        $mail->subject($parameters->getSubject());
        $this->setTestEmailContent($parameters, $mail);
        $this->setFromAddress($parameters, $mail);
        $this->setToAddresses($parameters, $mail);
        $this->addAttachment($parameters, $mail, $user);
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
     */
    private function setFromAddress(TestEmailRequest $parameters, Mail $mail): void
    {
        $fromAddresses = $this->mailResolver->parseEmailAddressField($parameters->getFrom());
        if (empty($fromAddresses)) {
            throw new EnvironmentException('Invalid from email address');
        }

        $fromAddress = $fromAddresses[0];
        $mail->from(new Address($fromAddress['email'], $fromAddress['name']));
    }

    private function setToAddresses(TestEmailRequest $parameters, Mail $mail): void
    {
        $toAddresses = $this->mailResolver->parseEmailAddressField($parameters->getTo());

        foreach ($toAddresses as $toAddress) {
            $mail->addTo(new Address($toAddress['email'], $toAddress['name']));
        }
    }

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    private function setTestEmailContent(TestEmailRequest $parameters, Mail $mail): void
    {
        match ($parameters->getContentType()) {
            TestEmailContentType::HTML->value, TestEmailContentType::TEXT->value =>
            $mail->html($parameters->getContent()),
            TestEmailContentType::DOCUMENT->value =>
            $this->handleDocumentContentType($parameters, $mail),
            default => throw new EnvironmentException('Invalid content type')
        };
    }

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    private function handleDocumentContentType(TestEmailRequest $parameters, Mail $mail): void
    {
        $document = $this->documentResolver->getByPath($parameters->getDocumentPath());
        if (!$document instanceof Email) {
            if ($document === null) {
                throw new NotFoundException('email document', $parameters->getDocumentPath(), 'path');
            }

            throw new InvalidElementTypeException($document->getType());
        }

        try {
            $mail->setDocument($document);
        } catch (Exception $exception) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to set email document: %s',
                    $exception->getMessage()
                )
            );
        }

        foreach ($parameters->getDocumentParameters() as $parameter) {
            $mail->setParam($parameter->getKey(), $parameter->getValue());
        }
    }

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    private function addAttachment(TestEmailRequest $parameters, Mail $mail, UserInterface $user): void
    {
        if ($parameters->getAttachmentId() === null) {
            return;
        }

        $attachment = $this->assetService->getAssetElement($user, $parameters->getAttachmentId());
        $mail->attach($attachment->getData(), $attachment->getFilename(), $attachment->getMimeType());
    }
}
