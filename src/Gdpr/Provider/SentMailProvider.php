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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider;

use Pimcore\Bundle\StudioBackendBundle\Email\Repository\EmailLogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\Tool\Email\Log;

/**
 * @internal
 */
final readonly class SentMailProvider implements DataProviderInterface
{
    public function __construct(
        private EmailLogRepositoryInterface $emailLogRepository,
        array $gdprConfig = []
    ) {
    }

    public function findData(FilterParameter $filter): Collection
    {
        $listing = $this->emailLogRepository->getFilteredListing($filter);

        $rows = array_map(
            fn (Log $entry) => new GdprDataRow([
                'id' => $entry->getId(),
                'from' => $entry->getFrom(),
                'to' => $entry->getTo(),
                'cc' => $entry->getCc(),
                'bcc' => $entry->getBcc(),
                'sentDate' => $entry->getSentDate(),
                'subject' => $entry->getSubject(),
                '__gdprIsDeletable' => true,
            ]),
            $listing->load()
        );

        return new Collection(
            totalItems: $listing->getTotalCount(),
            items: $rows
        );
    }


    /**
     * @throws NotFoundException
     */
    public function getSingleItemForDownload(int $id): array
    {
        $entry = $this->emailLogRepository->getExistingEntry($id);

        $data = [
            'id' => $entry->getId(),
            'documentId' => $entry->getDocumentId(),
            'from' => $entry->getFrom(),
            'to' => $entry->getTo(),
            'cc' => $entry->getCc(),
            'bcc' => $entry->getBcc(),
            'replyTo' => $entry->getReplyTo(),
            'sentDate' => $entry->getSentDate(),
            'subject' => $entry->getSubject(),
            'params' => $entry->getParams(),
        ];

        $htmlLog = $entry->getHtmlLog();
        if ($htmlLog !== false) {
            $data['htmlLog'] = $htmlLog;
        }

        $textLog = $entry->getTextLog();
        if ($textLog !== false) {
            $data['textLog'] = $textLog;
        }

        return $data;
    }

    public function getName(): string
    {
        return 'Sent Mails';
    }

    public function getKey(): string
    {
        return 'sent_mails';
    }

    public function getSortPriority(): int
    {
        return 6;
    }

    public function getRequiredPermissions(): array
    {
        return [UserPermissions::EMAILS->value];
    }
}
