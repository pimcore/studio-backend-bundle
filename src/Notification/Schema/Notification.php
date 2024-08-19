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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Notification',
    required: ['message', 'payload'],
    type: 'object'
)]
final class Notification extends NotificationListItem
{
    public function __construct(
        int $id,
        string $type,
        string $title,
        bool $read,
        bool $hasAttachment,
        int $sentDate,
        ?string $sender = null,
        #[Property(description: 'message', type: 'string', example: 'Notification message')]
        private readonly ?string $message = null,
        #[Property(description: 'payload', type: 'string', example: '{"key": "value"}')]
        private readonly ?string $payload = null,
    ) {
        parent::__construct($id, $type, $title, $read, $hasAttachment, $sentDate, $sender);
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }
}
