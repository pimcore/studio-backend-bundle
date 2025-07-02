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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'WebsiteSettingsUpdate',
    title: 'Website Settings Update',
    required: ['name', 'language', 'data', 'siteId'],
    type: 'object'
)]
final readonly class WebsiteSettingsUpdate
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'Updated Setting Title')]
        private string $name,
        #[Property(description: 'Language', type: 'string', example: 'en')]
        private string $language,
        #[Property(
            description: 'Data',
            example: true,
            anyOf: [
                new Schema(type: 'string'),
                new Schema(type: 'boolean'),
            ]
        )]
        private null|string|bool $data = null,
        #[Property(description: 'Site ID', type: 'integer', example: 1)]
        private ?int $siteId = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getData(): null|string|bool
    {
        return $this->data;
    }

    public function getSiteId(): ?int
    {
        return $this->siteId;
    }
}
