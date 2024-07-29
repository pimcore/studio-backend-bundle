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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

#[Schema(
    title: 'EmailLog',
    required: ['from', 'to', 'subject', 'sentDate', 'error'],
    type: 'object'
)]
final class EmailLogEntry implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'id', type: 'int', example: 23)]
        private readonly int $id,
        #[Property(description: 'from', type: 'string', example: 'from@pimcore.com')]
        private readonly string $from,
        #[Property(description: 'to', type: 'string', example: 'to@pimcore.com')]
        private readonly string $to,
        #[Property(description: 'subject', type: 'string', example: 'E-Mail subject')]
        private readonly string $subject,
        #[Property(description: 'sent date', type: 'integer', example: 1707312457)]
        private readonly int $sentDate,
        #[Property(description: 'error', type: 'string', example: 'Some error occurred')]
        private readonly ?string $error = null,
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getSentDate(): int
    {
        return $this->sentDate;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}