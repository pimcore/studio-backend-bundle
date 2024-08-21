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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_METHOD)]
final class ParentIdParameter extends QueryParameter
{
    public function __construct(string $description, bool $required = false, int $minimum = 1, ?int $example = 1)
    {
        parent::__construct(
            name: 'parentId',
            description: $description,
            in: 'query',
            required: $required,
            schema: new Schema(type: 'integer', minimum: $minimum, example: $example),
        );
    }
}
