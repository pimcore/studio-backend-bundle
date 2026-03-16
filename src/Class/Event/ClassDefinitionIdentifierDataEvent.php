<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionIdentifierData;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ClassDefinitionIdentifierDataEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.class_definition.identifier_data';

    public function __construct(private readonly ClassDefinitionIdentifierData $identifierData)
    {
        parent::__construct($this->identifierData);
    }

    public function getClassDefinitionIdentifierData(): ClassDefinitionIdentifierData
    {
        return $this->identifierData;
    }
}
