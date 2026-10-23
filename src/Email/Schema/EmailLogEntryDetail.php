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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'EmailLogDetail',
    required: ['bcc', 'cc', 'error'],
    type: 'object'
)]
final class EmailLogEntryDetail extends EmailLogEntry
{
    public function __construct(
        int $id,
        int $sentDate,
        bool $hasHtmlLog,
        bool $hasTextLog,
        bool $hasError,
        ?string $from = null,
        ?string $to = null,
        ?string $subject = null,
        #[Property(description: 'bcc', type: 'string', example: 'email@pimcore.com')]
        private readonly ?string $bcc = null,
        #[Property(description: 'cc', type: 'string', example: 'email@pimcore.com')]
        private readonly ?string $cc = null,
        #[Property(description: 'error', type: 'string', example: 'Some error occurred')]
        private readonly ?string $error = null,
    ) {
        parent::__construct($id, $sentDate, $hasHtmlLog, $hasTextLog, $hasError, $from, $to, $subject);
    }

    public function getBcc(): ?string
    {
        return $this->bcc;
    }

    public function getCc(): ?string
    {
        return $this->cc;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
