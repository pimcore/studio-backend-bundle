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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Relation Field Config',
    required: ['relation', 'field'],
    type: 'object'
)]
final readonly class RelationFieldConfig
{
    public function __construct(
        #[Property(description: 'Relation Getter', type: 'string', example: 'manufacturer')]
        private string $relation,
        #[Property(description: 'Field getter', type: 'string', example: 'name')]
        private string $field,
    ) {
    }

    public function getRelation(): string
    {
        return $this->relation;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
