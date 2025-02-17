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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionList;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ClassDefinitionListEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.class_definition.collection';

    public function __construct(private readonly ClassDefinitionList $classDefinition)
    {
        parent::__construct($this->classDefinition);
    }

    public function getClassDefinition(): ClassDefinitionList
    {
        return $this->classDefinition;
    }
}
