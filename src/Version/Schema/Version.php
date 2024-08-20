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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Version',
    required: ['id', 'cid', 'ctype', 'note', 'date', 'public', 'published', 'versionCount', 'autosave', 'user'],
    type: 'object'
)]
final class Version implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'version ID', type: 'integer', example: 2)]
        private readonly int $id,
        #[Property(description: 'element ID', type: 'integer', example: 10)]
        private readonly int $cid,
        #[Property(description: 'element type', type: 'string', example: 'object')]
        private readonly string $ctype,
        #[Property(description: 'note', type: 'string', example: 'some note')]
        private readonly string $note,
        #[Property(description: 'date', type: 'integer', example: 1712823182)]
        private readonly int $date,
        #[Property(description: 'public', type: 'bool', example: false)]
        private readonly bool $public,
        #[Property(description: 'published', type: 'bool', example: false)]
        private readonly bool $published,
        #[Property(description: 'version count', type: 'integer', example: 10)]
        private readonly int $versionCount,
        #[Property(description: 'autosave', type: 'bool', example: false)]
        private readonly bool $autosave,
        #[Property(description: 'user', type: VersionUser::class, example: '{"id":2,"name":"John Doe"}')]
        private readonly VersionUser $user,
        #[Property(description: 'scheduled', type: 'integer', example: null)]
        private readonly ?int $scheduled
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function getVersionCount(): int
    {
        return $this->versionCount;
    }

    public function isAutosave(): bool
    {
        return $this->autosave;
    }

    public function getUser(): VersionUser
    {
        return $this->user;
    }

    public function getScheduled(): ?int
    {
        return $this->scheduled;
    }
}
