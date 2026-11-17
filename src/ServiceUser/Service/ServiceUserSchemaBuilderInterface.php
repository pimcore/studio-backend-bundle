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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service;

use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUser as ServiceUserSchema;
use Pimcore\Model\User;

/**
 * @internal
 */
interface ServiceUserSchemaBuilderInterface
{
    /**
     * @param list<User> $serviceUsers
     *
     * @return list<ServiceUserSchema>
     */
    public function buildAll(array $serviceUsers): array;

    /**
     * @param array<int, array{active: int, latestExpiresAt: int}>|null $summaries token summaries by user id,
     *     fetched for all users at once by buildAll(); null makes this user's own query
     */
    public function build(User $serviceUser, ?array $summaries = null): ServiceUserSchema;
}
