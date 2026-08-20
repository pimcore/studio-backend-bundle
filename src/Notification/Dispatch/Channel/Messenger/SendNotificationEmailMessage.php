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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\Messenger;

/**
 * One notification email, fully resolved at dispatch time so the handler needs no lookups.
 * Scalars only — it rides the pimcore_core transport into a separate worker.
 *
 * @internal
 */
final readonly class SendNotificationEmailMessage
{
    public function __construct(
        private string $to,
        private string $toName,
        private string $locale,
        private string $subject,
        private string $title,
        private string $message,
        private string $link,
    ) {
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getToName(): string
    {
        return $this->toName;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLink(): string
    {
        return $this->link;
    }
}
