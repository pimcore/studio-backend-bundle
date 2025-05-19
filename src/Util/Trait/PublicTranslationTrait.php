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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NonPublicTranslationException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PublicTranslations;
use Symfony\Component\HttpFoundation\InputBag;
use function array_key_exists;
use function sprintf;

/**
 * @internal
 */
trait PublicTranslationTrait
{
    private const ARRAY_KEYS_INDEX = 'keys';

    /**
     * @throws NonPublicTranslationException
     */
    private function voteOnTranslation(InputBag $payload): bool
    {
        $parameters = $payload->all();
        if (!array_key_exists(self::ARRAY_KEYS_INDEX, $parameters)) {
            return false;
        }

        $nonPublicTranslations = array_diff(
            $parameters[self::ARRAY_KEYS_INDEX],
            PublicTranslations::PUBLIC_KEYS
        );

        if (!empty($nonPublicTranslations)) {
            throw new NonPublicTranslationException(
                401,
                sprintf(
                    'You have requested non public keys: %s',
                    implode(',', $nonPublicTranslations)
                )
            );
        }

        return true;
    }
}
