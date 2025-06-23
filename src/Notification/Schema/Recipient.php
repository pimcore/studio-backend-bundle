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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Recipient',
    required: ['id', 'recipientName'],
    type: 'object'
)]
final class Recipient implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the Recipient', type: 'integer', example: 1)]
        private readonly int $id,
        #[Property(description: 'User name or Group Name of the Recipient', type: 'string', example: 'Max Mustermann')]
        private readonly string $recipientName,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }
}
