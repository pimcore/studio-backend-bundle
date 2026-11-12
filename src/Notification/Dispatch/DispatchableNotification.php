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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch;

use Pimcore\Model\Element\ElementInterface;

/**
 * What a producer hands to the dispatcher. Carries no presentation state — whether it pops up
 * is a per-recipient preference resolved at publish time.
 */
final readonly class DispatchableNotification
{
    /**
     * @param int[] $recipientIds
     * @param array<string, mixed> $payload type-specific data for the frontend renderers
     */
    public function __construct(
        private string $typeId,
        private array $recipientIds,
        private string $title,
        private string $message,
        private ?int $senderId = null,
        private ?ElementInterface $linkedElement = null,
        private array $payload = [],
    ) {
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    /**
     * @return int[]
     */
    public function getRecipientIds(): array
    {
        return $this->recipientIds;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSenderId(): ?int
    {
        return $this->senderId;
    }

    public function getLinkedElement(): ?ElementInterface
    {
        return $this->linkedElement;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
