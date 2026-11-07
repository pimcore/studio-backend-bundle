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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolver;

/**
 * What the bridge answers, and - the part that matters to a client - which of its answers are the
 * same answer. `null` is not "this engine supports nothing": it is every case where the question
 * could not be put, and a client must not read core support out of it.
 *
 * @internal
 */
final class StoreEngineResolverTest extends Unit
{
    public function testACoreWithoutTheEngineColumnReportsNoEngine(): void
    {
        $resolver = new StoreEngineResolver(null);

        $this->assertNull(
            $resolver->readEngine(new EngineBlindStoreConfig()),
            'a store configuration that declares no getEngine() answers nothing, never a guess'
        );
    }

    public function testTheStoredEngineIsReportedVerbatim(): void
    {
        $storeConfig = new EngineAwareStoreConfig();
        $storeConfig->engine = 'document';

        $this->assertSame(
            'document',
            (new StoreEngineResolver(null))->readEngine($storeConfig),
            'the engine is reported as the store stores it, not normalised'
        );
    }

    public function testAnEngineThatIsNotAStringIsNoEngine(): void
    {
        $storeConfig = new EngineAwareStoreConfig();
        $storeConfig->engine = 42;

        $this->assertNull(
            (new StoreEngineResolver(null))->readEngine($storeConfig),
            'a column holding something that is not an engine name answers nothing'
        );
    }

    public function testTheDefaultEngineIsNullRatherThanANameOfItsOwn(): void
    {
        $storeConfig = new EngineAwareStoreConfig();
        $storeConfig->engine = null;

        $this->assertNull(
            (new StoreEngineResolver(null))->readEngine($storeConfig),
            'a store on the default engine stores no engine, and that is what is published'
        );
    }
}
