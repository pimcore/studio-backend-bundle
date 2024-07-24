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
    title: 'Blocklist',
    required: ['email', 'creationDate', 'modificationDate'],
    type: 'object'
)]
final class BlocklistEntry implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'email address', type: 'string', example: 'email@pimcore.com')]
        private readonly string $email,
        #[Property(description: 'creation date', type: 'integer', example: 1707312457)]
        private readonly int $creationDate,
        #[Property(description: 'modification date', type: 'integer', example: 1707312457)]
        private readonly ?int $modificationDate,
    ) {

    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }
}
