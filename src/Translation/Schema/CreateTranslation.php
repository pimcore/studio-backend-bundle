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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'CreateTranslation',
    title: 'Translation Create',
    description: 'Translation Crete Scheme for API',
    required: ['translationData'],
    type: 'object'
)]
final readonly class CreateTranslation
{
    public function __construct(
        #[Property(description: 'Translation Data', type: 'array', items: new Items(ref: CreateTranslationData::class))]
        private array $translationData = []
    ) {
    }

    /**
     * @return array<CreateTranslationData>
     */
    public function getTranslationData(): array
    {
        return $this->translationData;
    }
}
