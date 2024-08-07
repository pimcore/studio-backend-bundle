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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'UpdateCustomMetadata',
    required: ['name', 'language', 'type', 'data'],
    type: 'object'
)]
final readonly class UpdateCustomMetaData
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'custom_metadata')]
        private string $name,
        #[Property(description: 'Language', type: 'string', example: 'en')]
        private string $language,
        #[Property(description: 'Type', type: 'string', example: 'input')]
        private string $type,
        #[Property(description: 'Data', type: 'mixed', example: 'data')]
        private mixed $data
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
