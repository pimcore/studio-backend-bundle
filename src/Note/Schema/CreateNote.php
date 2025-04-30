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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'CreateNote',
    required: [
        'title',
        'description',
        'type',
    ],
    type: 'object'
)]
final readonly class CreateNote
{
    public function __construct(
        #[Property(description: 'title', type: 'string', example: 'Title of note')]
        private string $title,
        #[Property(description: 'description', type: 'string', example: 'Description of note')]
        private string $description,
        #[Property(description: 'type', type: 'string', example: 'Type of note')]
        private string $type
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
