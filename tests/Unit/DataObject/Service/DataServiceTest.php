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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use RuntimeException;

/**
 * @internal
 */
final class DataServiceTest extends Unit
{
    /**
     * Regression test for pimcore/platform-version#376: a validation failure raised while a data
     * adapter applies a value must reach the client as a 422, not as a generic saving error.
     *
     * @throws Exception
     */
    public function testUpdateEditableDataMapsValidationExceptionToFieldValidationFailed(): void
    {
        $this->expectException(FieldValidationFailedException::class);
        $this->expectExceptionMessage('field is mandatory');

        $this->updateWithAdapterThrowing(new ValidationException('field is mandatory'));
    }

    /**
     * @throws Exception
     */
    public function testUpdateEditableDataWrapsOtherExceptionsAsSavingFailed(): void
    {
        $this->expectException(ElementSavingFailedException::class);

        $this->updateWithAdapterThrowing(new RuntimeException('boom'));
    }

    /**
     * @throws Exception
     */
    private function updateWithAdapterThrowing(Exception $exception): void
    {
        $adapter = $this->makeEmpty(SetterDataInterface::class, [
            'getDataForSetter' => static function () use ($exception): mixed {
                throw $exception;
            },
        ]);
        $service = new DataService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $this->makeEmpty(DataAdapterServiceInterface::class, ['tryDataAdapter' => $adapter]),
            $this->makeEmpty(InheritanceServiceInterface::class),
            $this->makeEmpty(WorkflowDetailsServiceInterface::class),
        );
        $class = (new ClassDefinition())->setId('test')->addFieldDefinition('name', (new Input())->setName('name'));
        $element = $this->makeEmpty(Concrete::class, ['getId' => 1, 'getClass' => $class]);

        $service->updateEditableData($element, ['name' => 'value'], $this->makeEmpty(UserInterface::class));
    }
}
