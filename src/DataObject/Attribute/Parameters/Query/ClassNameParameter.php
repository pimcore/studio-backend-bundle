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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Attributes\Parameters\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Model\DataObject\ClassDefinition;

#[Attribute(Attribute::TARGET_METHOD)]
final class ClassNameParameter extends QueryParameter
{
    public function __construct()
    {
        // TODO Find better concept for this
        $classDefinitions = new ClassDefinition\Listing();

        parent::__construct(
            name: 'className',
            description: 'Filter by class.',
            in: 'query',
            required: false,
            schema: new Schema(
                type: 'string',
                enum:  array_map(static fn (ClassDefinition $def) => $def->getName(), $classDefinitions->load()),
                example: null
            ),
        );
    }
}
