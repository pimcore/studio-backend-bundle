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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ConsentAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\DataObject\Consent\Service;
use Pimcore\Model\DataObject\ClassDefinition\Data\Consent as ConsentDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\Element\Note;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ConsentAdapterTest extends Unit
{
    private const string KEY = 'probeConsent';

    private const string LANGUAGE = 'default';

    private const int GROUP_ID = 10;

    private const int KEY_ID = 100;

    private const int OLD_NOTE_ID = 7;

    private const int NEW_NOTE_ID = 99;

    /**
     * Regression test: inside a classification store the key is not a class field of the element, so
     * reading the current value off the element dies on the undefined getter.
     *
     * @throws Exception
     */
    public function testUnchangedClassificationStoreConsentKeepsItsNote(): void
    {
        $result = $this->callAdapter(
            ['consent' => true],
            $this->makeEmpty(Service::class, [
                'insertConsentNote' => Expected::never(),
                'insertRevokeNote' => Expected::never(),
            ]),
            $this->classificationStoreContext(new Consent(true, self::OLD_NOTE_ID))
        );

        $this->assertTrue($result->getConsent());
        $this->assertSame(self::OLD_NOTE_ID, $result->getNoteId());
    }

    /**
     * @throws Exception
     */
    public function testRevokedClassificationStoreConsentWritesRevokeNote(): void
    {
        $result = $this->callAdapter(
            ['consent' => false],
            $this->makeEmpty(Service::class, [
                'insertConsentNote' => Expected::never(),
                'insertRevokeNote' => Expected::once((new Note())->setId(self::NEW_NOTE_ID)),
            ]),
            $this->classificationStoreContext(new Consent(true, self::OLD_NOTE_ID))
        );

        $this->assertFalse($result->getConsent());
        $this->assertSame(self::NEW_NOTE_ID, $result->getNoteId());
    }

    /**
     * @throws Exception
     */
    public function testUnchangedClassFieldConsentKeepsItsNote(): void
    {
        $result = $this->callAdapter(
            ['consent' => true],
            $this->makeEmpty(Service::class, [
                'insertConsentNote' => Expected::never(),
                'insertRevokeNote' => Expected::never(),
            ]),
            null,
            $this->makeEmpty(Concrete::class, ['get' => new Consent(true, self::OLD_NOTE_ID)])
        );

        $this->assertTrue($result->getConsent());
        $this->assertSame(self::OLD_NOTE_ID, $result->getNoteId());
    }

    /**
     * @throws Exception
     */
    private function callAdapter(
        mixed $raw,
        Service $service,
        ?FieldContextData $contextData,
        ?Concrete $element = null
    ): Consent {
        $adapter = new ConsentAdapter($service);

        return $adapter->getDataForSetter(
            $element ?? $this->elementWithoutConsentField(),
            $this->makeEmpty(ConsentDefinition::class),
            self::KEY,
            [self::KEY => $raw],
            $this->makeEmpty(UserInterface::class),
            $contextData
        );
    }

    /**
     * @throws Exception
     */
    private function classificationStoreContext(mixed $storedValue): FieldContextData
    {
        return new FieldContextData(
            $this->make(Classificationstore::class, ['getLocalizedKeyValue' => $storedValue]),
            self::LANGUAGE,
            self::GROUP_ID,
            self::KEY_ID
        );
    }

    /**
     * What the real object does for a key that is not a class field of its own.
     *
     * @throws Exception
     */
    private function elementWithoutConsentField(): Concrete
    {
        return $this->makeEmpty(Concrete::class, [
            'get' => static fn (): mixed => throw new Exception(
                'Call to undefined method getProbeConsent in class Pimcore\Model\DataObject\TypeProbe1708'
            ),
        ]);
    }
}
