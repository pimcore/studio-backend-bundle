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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Maintenance;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;
use Pimcore\Maintenance\TaskInterface;
use function time;

/**
 * Deletes expired OAuth token records. Expired tokens are already rejected at
 * validation time (the revocation store only blocklists), so this is table
 * hygiene: a cheap single DELETE, safe to run every maintenance cycle.
 *
 * @internal
 */
final readonly class OAuthTokenGcTask implements TaskInterface
{
    public function __construct(private TokenRecordStoreInterface $store)
    {
    }

    public function execute(): void
    {
        $this->store->deleteExpired(time());
    }
}
