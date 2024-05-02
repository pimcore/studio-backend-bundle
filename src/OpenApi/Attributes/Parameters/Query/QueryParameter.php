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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter as OpenApiQueryParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Model\DataObject\ClassDefinition;

#[Attribute(Attribute::TARGET_METHOD)]
final class QueryParameter extends OpenApiQueryParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'query',
            description: 'Query for properties',
            in: 'query',
            required: false,
            schema: new Schema(type: 'string', example: null),
        );
    }
}
