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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'PatchCustomMetadata',
    required: ['name', 'language', 'type', 'data'],
    type: 'object'
)]
final readonly class PatchCustomMetadata
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'custom_metadata', nullable: false)]
        private string $name,
        #[Property(description: 'Language', type: 'string', example: 'en', nullable: true)]
        private ?string $language,
        #[Property(description: 'Type', type: 'string', example: 'input')]
        private string $type,
        #[Property(description: 'Data', type: 'string', example: 'data', nullable: true)]
        private mixed $data
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
