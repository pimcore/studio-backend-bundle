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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'UpdateVersion',
    type: 'object'
)]
final readonly class UpdateVersion
{
    public function __construct(
        #[Property(description: 'Public', type: 'boolean', example: null)]
        private ?bool $public,
        #[Property(description: 'Note', type: 'string', example: null)]
        private ?string $note
    ) {
    }

    public function getPublic(): ?bool
    {
        return $this->public;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }
}
