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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'CreateTranslationData',
    title: 'Translation Data for create',
    description: 'Translation Data Scheme for create endpoint of the API',
    required: ['key', 'type'],
    type: 'object'
)]
final readonly class CreateTranslationData
{
    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'my_translation_key')]
        private string $key,
        #[Property(description: 'Type', type: 'string', example: 'simple')]
        private string $type = 'simple',
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
