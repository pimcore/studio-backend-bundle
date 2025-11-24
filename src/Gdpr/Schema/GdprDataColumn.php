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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'GDPR Data Column',
    description: 'A single column definition for the GDPR data result grid',
    required: ['key', 'label'],
    type: 'object',
)]
final readonly class GdprDataColumn
{
    public function __construct(
        #[Property(
            description: 'Unique key of the column',
            type: 'string',
            example: 'email'
        )]
        private string $key,

        #[Property(
            description: 'Translated label of the column (for the header)',
            type: 'string',
            example: 'Email Address'
        )]
        private string $label,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
