<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Util\Traits;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NonPublicTranslationException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\PublicTranslations;
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
