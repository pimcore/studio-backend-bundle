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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attributes\Parameters\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class BinaryConfigParameter extends QueryParameter
{
    public function __construct(
        string $name,
        string $description = '',
        mixed $defaultValue = null,
        string $type = 'integer',
    ) {
        parent::__construct(
            name: $name,
            description: ucfirst($name) . $description,
            in: 'query',
            schema: new Schema(type: $type, example: $defaultValue),
        );
    }
}