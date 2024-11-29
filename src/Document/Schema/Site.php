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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'Site',
    title: 'Site',
    required: [
        'id',
        'domains',
        'domain',
    ],
    type: 'object'
)]
final class Site implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 0)]
        private readonly int $id,
        #[Property(description: 'Domains', type: 'array', items: new Items(type: 'string'), example: ['other_domain'])]
        private readonly array $domains,
        #[Property(description: 'Domain', type: 'string', example: 'main_site')]
        private readonly string $domain,
        #[Property(description: 'ID of the root', type: 'integer', example: 1)]
        private readonly ?int $rootId = null,
        #[Property(description: 'Root path', type: 'string', example: '/')]
        private readonly ?string $rootPath = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRootId(): ?int
    {
        return $this->rootId;
    }

    public function getRootPath(): ?string
    {
        return $this->rootPath;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}
