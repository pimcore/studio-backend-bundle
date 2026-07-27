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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\VersionCoauthor;

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
        private ?string $note,
        #[Property(
            description: 'Coauthor type, empty string clears it',
            type: 'string',
            maxLength: VersionCoauthor::MAX_TYPE_LENGTH,
            example: null
        )]
        private ?string $coauthorType = null,
        #[Property(
            description: 'Coauthor, empty string clears it',
            type: 'string',
            maxLength: VersionCoauthor::MAX_COAUTHOR_LENGTH,
            example: null
        )]
        private ?string $coauthor = null
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

    public function getCoauthorType(): ?string
    {
        return $this->coauthorType;
    }

    public function getCoauthor(): ?string
    {
        return $this->coauthor;
    }
}
