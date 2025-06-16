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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'WebsiteSetting',
    title: 'WebsiteSetting',
    required: ['id', 'name', 'type', 'data'],
    type: 'object'
)]
final class WebsiteSetting implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'int', example: 1)]
        private readonly int $id,
        #[Property(description: 'Name', type: 'string', example: 'site_title')]
        private readonly string $name,
        #[Property(description: 'Language', type: 'string', example: 'en')]
        private readonly string $language,
        #[Property(description: 'Type', type: 'string', example: 'text')]
        private readonly ?string $type = null,
        #[Property(description: 'Data', type: 'string', example: 'Some/setting/data')]
        private readonly ?string $data = null,
        #[Property(description: 'Site ID', type: 'integer', example: 1)]
        private readonly ?int $siteId = null,
        #[Property(description: 'Creation date', type: 'integer', example: null)]
        private readonly ?int $creationDate = null,
        #[Property(description: 'Modification date', type: 'integer', example: null)]
        private readonly ?int $modificationDate = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getSiteId(): ?int
    {
        return $this->siteId;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }
}