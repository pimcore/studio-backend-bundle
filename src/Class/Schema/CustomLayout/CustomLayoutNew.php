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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'CustomLayoutNew',
    title: 'Schema used to create custom layouts',
    required: [
        'name',
        'classId',
    ],
    type: 'object'
)]
final readonly class CustomLayoutNew
{
    public function __construct(
        #[Property(description: 'Name', type: 'string')]
        private string $name,
        #[Property(description: 'Data object class id', type: 'integer')]
        private int $classId
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClassId(): int
    {
        return $this->classId;
    }
}
