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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Add Perspective Config',
    required: [
        'name',
    ],
    type: 'object'
)]
readonly class AddPerspectiveConfig
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'Cars')]
        private string $name
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
