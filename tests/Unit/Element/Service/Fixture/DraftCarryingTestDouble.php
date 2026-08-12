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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service\Fixture;

use Pimcore\Model\Version;

/**
 * Records what the resolver asked for and answers with a canned version.
 *
 * getLatestVersion() is not declared on the models — it lives on the Dao, reached via
 * __call. A mock cannot intercept it (the mocked __call answers null and the test passes
 * proving nothing), so a subclass declares it for real.
 *
 * @internal
 */
trait DraftCarryingTestDouble
{
    public ?Version $stubVersion = null;

    public int $calls = 0;

    public ?int $seenUserId = null;

    public function getLatestVersion(?int $userId = null, bool $includingPublished = false): ?Version
    {
        $this->calls++;
        $this->seenUserId = $userId;

        return $this->stubVersion;
    }
}
