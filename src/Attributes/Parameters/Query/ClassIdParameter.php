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

namespace Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Model\DataObject\ClassDefinition;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ClassIdParameter extends QueryParameter
{
    public function __construct()
    {
        // TODO Find better concept for this
        $classDefinitions = new ClassDefinition\Listing();
        $classNames = [];
        $description = 'Filter by class.';
        foreach ($classDefinitions->load() as $classDefinition) {
            $classNames[] = $classDefinition->getId();
            $description .= '<br>' . $classDefinition->getId() . ' => ' . $classDefinition->getName();
        }
        $description .= '<br><br>';

        parent::__construct(
            name: 'classId',
            description: $description,
            in: 'query',
            required: false,
            schema: new Schema(type: 'string', enum:  $classNames, example: null),
        );
    }
}
