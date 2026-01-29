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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

/**
 * @internal
 */
final class UserTopicService implements UserTopicServiceInterface
{
    private const string USER_TOPIC_PREFIX = 'studio-backend-default/user/';

    private const string USER_TOPIC_WILDCARD = '*';

    public function getUserTopic(int $userId): string
    {
        return self::USER_TOPIC_PREFIX . $userId;
    }

    public function getWildcardTopic(): string
    {
        return self::USER_TOPIC_WILDCARD;
    }
}
