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
final class PathIncludeDescendantsParameter extends QueryParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'pathIncludeDescendants',
            description: 'Include all descendants in the result.',
            in: 'query',
            required: false,
            schema: new Schema(type: 'boolean', example: null),
        );
    }
}
