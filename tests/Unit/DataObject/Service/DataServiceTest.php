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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\RemoteStorageDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
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
     * A refused permission raised while a data adapter applies a value must reach the client as a 403.
     *
     * @throws Exception
     */
    public function testUpdateEditableDataKeepsForbiddenException(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->updateWithAdapterThrowing(new ForbiddenException());
    }

    /**
     * @throws Exception
     */
    public function testUpdateEditableDataSkipsRemoteWritingAdapterWhenRemoteChangesAreNotApplied(): void
    {
        $calls = 0;
        $adapter = new class($calls) implements SetterDataInterface, RemoteStorageDataInterface {
            public function __construct(private int &$calls)
            {
            }

            public function getDataForSetter(
                Concrete $element,
                ClassDefinition\Data $fieldDefinition,
                string $key,
                array $data,
                UserInterface $user,
                ?FieldContextData $contextData = null,
                bool $isPatch = false
            ): mixed {
                $this->calls++;

                return null;
            }
        };

        $this->updateWith($adapter, false);
        $this->assertSame(0, $calls, 'the remote writing adapter must not run for a draft');

        $this->updateWith($adapter, true);
        $this->assertSame(1, $calls, 'the remote writing adapter runs for a real save');
    }

    /**
     * @throws Exception
     */
    public function testUpdateEditableDataStillAppliesOtherAdaptersWhenRemoteChangesAreNotApplied(): void
    {
        $calls = 0;
        $adapter = $this->makeEmpty(SetterDataInterface::class, [
            'getDataForSetter' => static function () use (&$calls): mixed {
                $calls++;

                return 'value';
            },
        ]);

        $this->updateWith($adapter, false);

        $this->assertSame(1, $calls);
    }

    /**
     * @throws Exception
     */
    private function updateWith(SetterDataInterface $adapter, bool $applyRemoteChanges): void
    {
        $this->createUpdateService($adapter)->updateEditableData(
            $this->createUpdateElement(),
            ['name' => 'value'],
            $this->makeEmpty(UserInterface::class),
            $applyRemoteChanges
        );
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

        $this->createUpdateService($adapter)->updateEditableData(
            $this->createUpdateElement(),
            ['name' => 'value'],
            $this->makeEmpty(UserInterface::class)
        );
    }

    /**
     * @throws Exception
     */
    private function createUpdateService(SetterDataInterface $adapter): DataService
    {
        return new DataService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $this->makeEmpty(DataAdapterServiceInterface::class, ['tryDataAdapter' => $adapter]),
            $this->makeEmpty(InheritanceServiceInterface::class),
            $this->makeEmpty(WorkflowDetailsServiceInterface::class),
        );
    }

    /**
     * @throws Exception
     */
    private function createUpdateElement(): Concrete
    {
        $class = (new ClassDefinition())->setId('test')->addFieldDefinition('name', (new Input())->setName('name'));

        return $this->makeEmpty(Concrete::class, ['getId' => 1, 'getClass' => $class]);
    }
}
