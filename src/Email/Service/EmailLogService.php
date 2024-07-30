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

use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Email\Event\PreResponse\EmailLogEntryEvent;
use Pimcore\Bundle\StudioBackendBundle\Email\Repository\EmailLogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\EmailLogEntry;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Mail;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Tool\Email\Log;
use Pimcore\Twig\Extension\Templating\Placeholder\Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
/**
 * @internal
 */
final readonly class EmailLogService implements EmailLogServiceInterface
{
    public function __construct(
        private DocumentResolverInterface $documentResolver,
        private EmailLogRepositoryInterface $emailLogRepository,
        private EventDispatcherInterface $eventDispatcher,
        private MailServiceInterface $mailService
    ) {
    }

    public function listEntries(CollectionParameters $parameters): Collection
    {
        $list = [];
        $listing = $this->emailLogRepository->getListing($parameters);
        foreach ($listing as $listEntry) {
            $entry = new EmailLogEntry(
                $listEntry->getId(),
                $listEntry->getSentDate(),
                $listEntry->getEmailLogExistsHtml() === 1,
                $listEntry->getEmailLogExistsText() === 1,
                $this->sanitizeEmailAddress($listEntry->getFrom()),
                $this->sanitizeEmailAddress($listEntry->getTo()),
                $listEntry->getSubject(),
                $listEntry->getError(),
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
     * @throws NotFoundException
     */
    public function getEntry(int $id): Log
    {
        return $this->emailLogRepository->getExistingEntry($id);
    }

    /**
     * @throws NotFoundException
     */
    public function deleteEntry(int $id): void
    {
        $this->emailLogRepository->deleteEntry($id);
    }

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function getEmailFromLogEntry(Log $emailLogEntry): Mail
    {
        $mail = new Mail();
        $mail->subject($emailLogEntry->getSubject());
        $this->mailService->setMailFromAddress($emailLogEntry->getFrom(), $mail);
        $this->setEmailContentFromLog($emailLogEntry, $mail);
        $mail->preventDebugInformationAppending();
        $mail->setIgnoreDebugMode(true);

        return $mail;
    }
    
     /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    private function setEmailContentFromLog(Log $emailLogEntry, Mail $mail): void
    {
        $documentId = $emailLogEntry->getDocumentId();
        if ($documentId !== null) {
            $this->setEmailDocumentContent($documentId, $emailLogEntry, $mail);

            return;
        }

        $htmlLog = $emailLogEntry->getHtmlLog();
        if ($htmlLog) {
            $mail->html($htmlLog);

            return;
        }

        $textLog = $emailLogEntry->getTextLog();
        if ($textLog) {
            $mail->text($textLog);
        }
    }

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    private function setEmailDocumentContent(int $documentId, Log $emailLogEntry, Mail $mail): void
    {
        $document = $this->documentResolver->getById($documentId);
        if (!$document) {
            throw new NotFoundException('email document', $documentId);
        }
        $this->mailService->setMailDocumentContent($document, $mail);
        try {
            $params = $emailLogEntry->getParams();
            foreach ($params as $entry) {
                $value = isset($entry['children']) && is_array($entry['children'])
                    ? array_column(
                        array_map([$this, 'parseParamValueFromLog'], $entry['children']),
                        null, 
                        'key'
                    )
                    : $this->parseParamValueFromLog($entry);

                $mail->setParam($entry['key'], $value);
            }
        } catch (Exception $exception) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to get email document params: %s',
                    $exception->getMessage()
                )
            );
        }
    }

    private function parseParamValueFromLog(array $parameter): mixed
    {
        $data = $parameter['data'];
        if ($data['type'] !== ElementTypes::TYPE_OBJECT || !isset($data['objectClass'])) {
            return $data['value'];
        }

        $class = '\\' . ltrim($data['objectClass'], '\\');
        if (!empty($data['objectId']) && is_subclass_of($class, ElementInterface::class)) {
            $obj = $class::getById($data['objectId']);
            if ($obj !== null) {
                return $obj;
            }
        }

        return null;
    }

    private function sanitizeEmailAddress(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $pattern = '/"([^"]+)" <([^>]+)>/';
        $replacement = '$1 ($2)';

        return preg_replace($pattern, $replacement, $email);
    }
}
