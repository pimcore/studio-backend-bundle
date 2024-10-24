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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path;

use Attribute;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class StringParameter extends PathParameter
{
    public function __construct(
        string $name,
        string $example,
        string $description,
        bool $required = true,
    ) {
        parent::__construct(
            name: $name,
            description: $description,
            in: 'path',
            required: $required,
            schema: new Schema(type: 'string', example: $example),
        );
    }
}
